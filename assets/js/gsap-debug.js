// GSAP Debug Script
console.log('=== GSAP DEBUG SCRIPT LOADED ===');
console.log('Debug script loaded at:', new Date().toISOString());

// Check if GSAP is loaded
if (typeof gsap !== 'undefined') {
    console.log('✅ GSAP is loaded in debug script');
    console.log('GSAP version:', gsap.version);
    console.log('GSAP methods available:', Object.keys(gsap));
    
    // Test a simple GSAP animation
    const testElement = document.createElement('div');
    testElement.style.width = '50px';
    testElement.style.height = '50px';
    testElement.style.backgroundColor = 'red';
    testElement.style.position = 'fixed';
    testElement.style.top = '50%';
    testElement.style.right = '50%';
    testElement.style.zIndex = '9999';
    testElement.textContent = 'GSAP';
    document.body.appendChild(testElement);
    
    // Animate the test element
    gsap.to(testElement, {
        rotation: 360,
        duration: 2,
        x: 100,
        y: 100,
        ease: 'power2.inOut',
        onComplete: () => {
            console.log('✅ GSAP animation test completed successfully');
            gsap.to(testElement, {
                opacity: 0,
                duration: 1,
                delay: 2,
                onComplete: () => {
                    document.body.removeChild(testElement);
                }
            });
        }
    });
    
} else {
    console.error('❌ GSAP is NOT loaded in debug script');
    console.log('Available global variables:', Object.keys(window).filter(key => key.includes('gsap')));
}

// Check if ScrollTrigger is loaded
if (typeof gsap !== 'undefined' && typeof gsap.ScrollTrigger !== 'undefined') {
    console.log('✅ ScrollTrigger is loaded');
} else {
    console.warn('⚠️ ScrollTrigger is not loaded');
}

// Log all script tags to see loading order
console.log('=== SCRIPT LOADING ORDER ===');
const scripts = document.querySelectorAll('script[src*="gsap"]');
scripts.forEach((script, index) => {
    console.log(`${index + 1}. ${script.src}`);
}); 