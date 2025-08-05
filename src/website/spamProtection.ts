import axios from 'axios';
import dns from 'dns';
import { promisify } from 'util';

const dnsResolve4 = promisify(dns.resolve4);

interface SpamCheckResult {
    isSpam: boolean;
    reason?: string;
}

export async function checkAkismet(
    apiKey: string,
    ip: string,
    userAgent: string,
    username: string,
    email: string,
    siteUrl: string
): Promise<SpamCheckResult> {
    try {
        const akismetClient = require('akismet-api').client({
            key: apiKey,
            blog: siteUrl
        });

        // Verify API key
        const isValid = await new Promise((resolve) => {
            akismetClient.verifyKey((err: any, valid: boolean) => {
                if (err) {
                    console.error('Akismet key verification error:', err);
                    resolve(false);
                } else {
                    resolve(valid);
                }
            });
        });

        if (!isValid) {
            console.error('Invalid Akismet API key');
            return { isSpam: false }; // Allow registration if key is invalid
        }

        // Check if the registration is spam
        const isSpam = await new Promise((resolve) => {
            akismetClient.checkSpam({
                user_ip: ip,
                user_agent: userAgent,
                comment_type: 'signup',
                comment_author: username,
                comment_author_email: email
            }, (err: any, spam: boolean) => {
                if (err) {
                    console.error('Akismet check error:', err);
                    resolve(false); // Allow registration on error
                } else {
                    resolve(spam);
                }
            });
        });

        return {
            isSpam: isSpam as boolean,
            reason: isSpam ? 'Akismet identified this registration as spam' : undefined
        };
    } catch (error) {
        console.error('Akismet error:', error);
        return { isSpam: false }; // Allow registration on error
    }
}

export async function checkProjectHoneypot(
    apiKey: string,
    ip: string
): Promise<SpamCheckResult> {
    try {
        // Project Honeypot uses DNS lookups
        // Format: [API_KEY].[REVERSED_IP].dnsbl.httpbl.org
        const reversedIp = ip.split('.').reverse().join('.');
        const query = `${apiKey}.${reversedIp}.dnsbl.httpbl.org`;

        try {
            const results = await dnsResolve4(query);
            
            if (results.length > 0) {
                // Parse the response: 127.DAY_LAST_SEEN.THREAT_SCORE.TYPE
                const parts = results[0].split('.');
                
                if (parts.length === 4 && parts[0] === '127') {
                    const daysSinceLastSeen = parseInt(parts[1]);
                    const threatScore = parseInt(parts[2]);
                    const visitorType = parseInt(parts[3]);
                    
                    // Type flags:
                    // 0 = Search Engine
                    // 1 = Suspicious
                    // 2 = Harvester
                    // 4 = Comment Spammer
                    
                    // If threat score is high or it's a known spammer/harvester
                    if (threatScore >= 25 || (visitorType & 6) !== 0) {
                        let reasons = [];
                        if (visitorType & 2) reasons.push('harvester');
                        if (visitorType & 4) reasons.push('comment spammer');
                        if (threatScore >= 25) reasons.push(`high threat score (${threatScore})`);
                        
                        return {
                            isSpam: true,
                            reason: `Project Honeypot: ${reasons.join(', ')}`
                        };
                    }
                }
            }
        } catch (error: any) {
            // NXDOMAIN means IP is not in the database (good)
            if (error.code === 'ENOTFOUND') {
                return { isSpam: false };
            }
            throw error;
        }

        return { isSpam: false };
    } catch (error) {
        console.error('Project Honeypot error:', error);
        return { isSpam: false }; // Allow registration on error
    }
}

export async function checkSpam(
    settings: any,
    ip: string,
    userAgent: string,
    username: string,
    email: string,
    siteUrl: string
): Promise<SpamCheckResult> {
    const results: SpamCheckResult[] = [];

    // Check Akismet if enabled
    if (settings.akismet_enabled === 'true' && settings.akismet_api_key) {
        const akismetResult = await checkAkismet(
            settings.akismet_api_key,
            ip,
            userAgent,
            username,
            email,
            siteUrl
        );
        results.push(akismetResult);
    }

    // Check Project Honeypot if enabled
    if (settings.honeypot_enabled === 'true' && settings.honeypot_api_key) {
        const honeypotResult = await checkProjectHoneypot(
            settings.honeypot_api_key,
            ip
        );
        results.push(honeypotResult);
    }

    // If any service identifies as spam, block the registration
    const spamResult = results.find(r => r.isSpam);
    if (spamResult) {
        return spamResult;
    }

    return { isSpam: false };
}