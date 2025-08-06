// Mobile Touch Controls for 2004Scape
(function() {
    'use strict';
    
    // Check if device is touch-enabled
    const isTouchDevice = ('ontouchstart' in window) || 
                         (navigator.maxTouchPoints > 0) || 
                         (navigator.msMaxTouchPoints > 0);
    
    if (!isTouchDevice) return;
    
    let canvas;
    let touchStartX = 0;
    let touchStartY = 0;
    let touchStartTime = 0;
    let longPressTimer = null;
    let isLongPress = false;
    let lastTapTime = 0;
    let lastTapX = 0;
    let lastTapY = 0;
    
    // Constants
    const LONG_PRESS_DURATION = 500; // ms
    const DOUBLE_TAP_THRESHOLD = 300; // ms
    const DOUBLE_TAP_DISTANCE = 50; // pixels
    const PINCH_THRESHOLD = 30; // pixels
    
    // Initialize when DOM is ready
    function init() {
        canvas = document.getElementById('canvas');
        if (!canvas) return;
        
        // Prevent default touch behaviors
        canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
        canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
        canvas.addEventListener('touchend', handleTouchEnd, { passive: false });
        canvas.addEventListener('touchcancel', handleTouchCancel, { passive: false });
        
        // Prevent context menu on long press
        canvas.addEventListener('contextmenu', (e) => e.preventDefault());
        
        // Add visual feedback for touches
        addTouchFeedback();
    }
    
    function handleTouchStart(e) {
        e.preventDefault();
        
        if (e.touches.length === 1) {
            // Single touch
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            touchStartTime = Date.now();
            
            // Start long press timer
            isLongPress = false;
            longPressTimer = setTimeout(() => {
                isLongPress = true;
                triggerRightClick(touchStartX, touchStartY);
                showTouchFeedback(touchStartX, touchStartY, 'long-press');
            }, LONG_PRESS_DURATION);
            
        } else if (e.touches.length === 2) {
            // Two finger touch - prepare for pinch zoom
            handlePinchStart(e);
        }
    }
    
    function handleTouchMove(e) {
        e.preventDefault();
        
        if (e.touches.length === 1 && !isLongPress) {
            // Cancel long press if finger moves too much
            const touch = e.touches[0];
            const deltaX = Math.abs(touch.clientX - touchStartX);
            const deltaY = Math.abs(touch.clientY - touchStartY);
            
            if (deltaX > 10 || deltaY > 10) {
                clearTimeout(longPressTimer);
            }
            
            // Trigger mouse move
            triggerMouseMove(touch.clientX, touch.clientY);
            
        } else if (e.touches.length === 2) {
            // Handle pinch zoom
            handlePinchMove(e);
        }
    }
    
    function handleTouchEnd(e) {
        e.preventDefault();
        clearTimeout(longPressTimer);
        
        if (!isLongPress && e.changedTouches.length === 1) {
            const touch = e.changedTouches[0];
            const currentTime = Date.now();
            const tapDuration = currentTime - touchStartTime;
            
            // Check for double tap
            const timeSinceLastTap = currentTime - lastTapTime;
            const distance = Math.sqrt(
                Math.pow(touch.clientX - lastTapX, 2) + 
                Math.pow(touch.clientY - lastTapY, 2)
            );
            
            if (timeSinceLastTap < DOUBLE_TAP_THRESHOLD && 
                distance < DOUBLE_TAP_DISTANCE) {
                // Double tap detected
                triggerDoubleClick(touch.clientX, touch.clientY);
                showTouchFeedback(touch.clientX, touch.clientY, 'double-tap');
                lastTapTime = 0; // Reset to prevent triple tap
            } else if (tapDuration < 200) {
                // Single tap
                triggerClick(touch.clientX, touch.clientY);
                showTouchFeedback(touch.clientX, touch.clientY, 'tap');
                lastTapTime = currentTime;
                lastTapX = touch.clientX;
                lastTapY = touch.clientY;
            }
        }
        
        isLongPress = false;
    }
    
    function handleTouchCancel(e) {
        e.preventDefault();
        clearTimeout(longPressTimer);
        isLongPress = false;
    }
    
    // Pinch zoom handling
    let initialPinchDistance = 0;
    
    function handlePinchStart(e) {
        if (e.touches.length === 2) {
            const dx = e.touches[0].clientX - e.touches[1].clientX;
            const dy = e.touches[0].clientY - e.touches[1].clientY;
            initialPinchDistance = Math.sqrt(dx * dx + dy * dy);
        }
    }
    
    function handlePinchMove(e) {
        if (e.touches.length === 2 && initialPinchDistance > 0) {
            const dx = e.touches[0].clientX - e.touches[1].clientX;
            const dy = e.touches[0].clientY - e.touches[1].clientY;
            const currentDistance = Math.sqrt(dx * dx + dy * dy);
            const delta = currentDistance - initialPinchDistance;
            
            if (Math.abs(delta) > PINCH_THRESHOLD) {
                // Trigger zoom
                if (delta > 0) {
                    triggerZoomIn();
                } else {
                    triggerZoomOut();
                }
                initialPinchDistance = currentDistance;
            }
        }
    }
    
    // Convert touch to mouse events
    function triggerClick(x, y) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        const canvasX = (x - rect.left) * scaleX;
        const canvasY = (y - rect.top) * scaleY;
        
        simulateMouseEvent('mousedown', canvasX, canvasY, 0);
        simulateMouseEvent('mouseup', canvasX, canvasY, 0);
        simulateMouseEvent('click', canvasX, canvasY, 0);
    }
    
    function triggerRightClick(x, y) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        const canvasX = (x - rect.left) * scaleX;
        const canvasY = (y - rect.top) * scaleY;
        
        simulateMouseEvent('mousedown', canvasX, canvasY, 2);
        simulateMouseEvent('mouseup', canvasX, canvasY, 2);
        simulateMouseEvent('contextmenu', canvasX, canvasY, 2);
    }
    
    function triggerDoubleClick(x, y) {
        triggerClick(x, y);
        setTimeout(() => triggerClick(x, y), 50);
    }
    
    function triggerMouseMove(x, y) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        const canvasX = (x - rect.left) * scaleX;
        const canvasY = (y - rect.top) * scaleY;
        
        simulateMouseEvent('mousemove', canvasX, canvasY, 0);
    }
    
    function simulateMouseEvent(type, x, y, button) {
        const event = new MouseEvent(type, {
            bubbles: true,
            cancelable: true,
            view: window,
            clientX: x,
            clientY: y,
            screenX: x,
            screenY: y,
            button: button,
            buttons: button === 0 ? 1 : (button === 2 ? 2 : 0),
            which: button + 1
        });
        
        canvas.dispatchEvent(event);
    }
    
    // Zoom controls
    function triggerZoomIn() {
        // Simulate scroll wheel up
        const event = new WheelEvent('wheel', {
            deltaY: -100,
            bubbles: true,
            cancelable: true
        });
        canvas.dispatchEvent(event);
    }
    
    function triggerZoomOut() {
        // Simulate scroll wheel down
        const event = new WheelEvent('wheel', {
            deltaY: 100,
            bubbles: true,
            cancelable: true
        });
        canvas.dispatchEvent(event);
    }
    
    // Visual feedback
    function addTouchFeedback() {
        const style = document.createElement('style');
        style.textContent = `
            .touch-feedback {
                position: fixed;
                pointer-events: none;
                border-radius: 50%;
                transform: translate(-50%, -50%);
                animation: touch-fade 0.5s ease-out;
                z-index: 10000;
            }
            
            .touch-feedback.tap {
                width: 40px;
                height: 40px;
                border: 3px solid rgba(255, 215, 0, 0.8);
                background: rgba(255, 215, 0, 0.2);
            }
            
            .touch-feedback.long-press {
                width: 60px;
                height: 60px;
                border: 3px solid rgba(255, 100, 100, 0.8);
                background: rgba(255, 100, 100, 0.2);
            }
            
            .touch-feedback.double-tap {
                width: 50px;
                height: 50px;
                border: 3px solid rgba(100, 255, 100, 0.8);
                background: rgba(100, 255, 100, 0.2);
            }
            
            @keyframes touch-fade {
                from {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(0.5);
                }
                to {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(1.5);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    function showTouchFeedback(x, y, type) {
        const feedback = document.createElement('div');
        feedback.className = `touch-feedback ${type}`;
        feedback.style.left = x + 'px';
        feedback.style.top = y + 'px';
        
        document.body.appendChild(feedback);
        
        setTimeout(() => {
            feedback.remove();
        }, 500);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Export for debugging
    window.TouchControls = {
        isEnabled: () => isTouchDevice,
        reinit: init
    };
})();