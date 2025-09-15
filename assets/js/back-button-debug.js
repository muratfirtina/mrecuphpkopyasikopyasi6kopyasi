/**
 * Mr ECU - Browser Back Button Debug Test Script
 * Bu script problemin çözülüp çözülmediğini test etmek için kullanılır
 */

console.log('🔧 MR ECU - Browser Back Button Debug Test Started');

// Test fonksiyonları
const BackButtonDebugger = {
    
    // 1. ECU Spinner varlığını test et
    testSpinnerElement: function() {
        const spinner = document.getElementById('ecuSpinner');
        console.log('🎯 ECU Spinner Element Test:');
        console.log('- Element exists:', !!spinner);
        console.log('- Element:', spinner);
        return !!spinner;
    },
    
    // 2. Global kontrol fonksiyonlarını test et
    testGlobalControls: function() {
        console.log('🎯 Global Control Functions Test:');
        console.log('- ECUSpinnerControl exists:', typeof window.ECUSpinnerControl !== 'undefined');
        console.log('- ECUSpinnerControl object:', window.ECUSpinnerControl);
        console.log('- showECUSpinner function exists:', typeof window.showECUSpinner !== 'undefined');
        console.log('- hideECUSpinner function exists:', typeof window.hideECUSpinner !== 'undefined');
        
        return typeof window.ECUSpinnerControl !== 'undefined';
    },
    
    // 3. Event listener'ları test et
    testEventListeners: function() {
        console.log('🎯 Event Listeners Test:');
        
        // Check for duplicate pageshow events
        let pageShowCount = 0;
        const originalAddEventListener = window.addEventListener;
        window.addEventListener = function(type, listener, options) {
            if (type === 'pageshow') {
                pageShowCount++;
                console.log(`- PageShow listener #${pageShowCount} added`);
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
        
        // Restore original function
        setTimeout(() => {
            window.addEventListener = originalAddEventListener;
        }, 100);
        
        // Test pageshow event
        console.log('- Triggering pageshow event (persisted: false)...');
        window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: false }));
        
        console.log('- Triggering pageshow event (persisted: true)...');
        window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
        
        // Popstate event test
        console.log('- Triggering popstate event...');
        window.dispatchEvent(new PopStateEvent('popstate', { state: { test: true } }));
        
        return true;
    },
    
    // 4. Navigation link test
    testNavigationLinks: function() {
        console.log('🎯 Navigation Links Test:');
        const navLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([target="_blank"]):not([data-bs-toggle])');
        console.log('- Found navigation links:', navLinks.length);
        console.log('- Links:', Array.from(navLinks).slice(0, 5).map(link => link.href));
        
        return navLinks.length > 0;
    },
    
    // 5. Spinner manuel test
    testSpinnerManual: function() {
        console.log('🎯 Manual Spinner Test:');
        
        if (window.ECUSpinnerControl) {
            console.log('- Showing spinner for 2 seconds...');
            window.ECUSpinnerControl.show(2000);
            
            setTimeout(() => {
                console.log('- Spinner should have closed automatically');
            }, 2100);
        } else {
            console.log('- ECUSpinnerControl not available');
        }
        
        return true;
    },
    
    // 6. CSS dosyası test
    testSpinnerCSS: function() {
        console.log('🎯 Spinner CSS Test:');
        
        const spinner = document.getElementById('ecuSpinner');
        if (spinner) {
            const styles = window.getComputedStyle(spinner);
            console.log('- Spinner display:', styles.display);
            console.log('- Spinner position:', styles.position);
            console.log('- Spinner z-index:', styles.zIndex);
            console.log('- Spinner background:', styles.backgroundColor);
        }
        
        return true;
    },
    
    // 7. Browser compatibility test
    testBrowserCompatibility: function() {
        console.log('🎯 Browser Compatibility Test:');
        console.log('- PopState API:', 'onpopstate' in window);
        console.log('- PageShow API:', 'onpageshow' in window);
        console.log('- History API:', !!(window.history && window.history.pushState));
        console.log('- Fetch API:', typeof fetch !== 'undefined');
        console.log('- Promise API:', typeof Promise !== 'undefined');
        
        return true;
    },
    
    // 8. Double spinner detection test
    testDoubleSpinner: function() {
        console.log('🎯 Double Spinner Detection Test:');
        
        let spinnerShowCount = 0;
        const spinner = document.getElementById('ecuSpinner');
        
        if (spinner) {
            // Monitor display changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const display = spinner.style.display;
                        if (display === 'flex') {
                            spinnerShowCount++;
                            console.log(`- Spinner shown #${spinnerShowCount} at:`, new Date().toLocaleTimeString());
                        }
                    }
                });
            });
            
            observer.observe(spinner, {
                attributes: true,
                attributeFilter: ['style']
            });
            
            // Stop observing after 5 seconds
            setTimeout(() => {
                observer.disconnect();
                console.log(`- Total spinner shows detected: ${spinnerShowCount}`);
                
                if (spinnerShowCount > 1) {
                    console.warn('⚠️ Multiple spinner shows detected! This might indicate the double spinner issue.');
                } else {
                    console.log('✅ No double spinner issue detected.');
                }
            }, 5000);
            
            console.log('- Monitoring spinner for 5 seconds...');
        }
        
        return true;
    },
    
    // 9. PageShow event behavior test
    testPageShowBehavior: function() {
        console.log('🎯 PageShow Event Behavior Test:');
        
        // Create a test counter
        let pageShowEvents = 0;
        
        function testPageShowHandler(event) {
            pageShowEvents++;
            console.log(`- PageShow event #${pageShowEvents}:`, {
                persisted: event.persisted,
                type: event.type,
                timeStamp: event.timeStamp
            });
        }
        
        // Add temporary listener
        window.addEventListener('pageshow', testPageShowHandler);
        
        // Test with both persisted states
        setTimeout(() => {
            console.log('- Testing fresh load pageshow...');
            window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: false }));
        }, 100);
        
        setTimeout(() => {
            console.log('- Testing cache load pageshow...');
            window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
        }, 200);
        
        // Cleanup
        setTimeout(() => {
            window.removeEventListener('pageshow', testPageShowHandler);
            console.log(`- PageShow test completed. Total events: ${pageShowEvents}`);
        }, 500);
        
        return true;
    },
    
    // Tüm testleri çalıştır
    runAllTests: function() {
        console.log('🚀 Running All Debug Tests...');
        console.log('==========================================');
        
        const tests = [
            { name: 'Spinner Element', test: this.testSpinnerElement },
            { name: 'Global Controls', test: this.testGlobalControls },
            { name: 'Event Listeners', test: this.testEventListeners },
            { name: 'Navigation Links', test: this.testNavigationLinks },
            { name: 'Spinner CSS', test: this.testSpinnerCSS },
            { name: 'Browser Compatibility', test: this.testBrowserCompatibility },
            { name: 'Double Spinner Detection', test: this.testDoubleSpinner },
            { name: 'PageShow Behavior', test: this.testPageShowBehavior },
            { name: 'Manual Spinner', test: this.testSpinnerManual }
        ];
        
        const results = tests.map(({ name, test }) => {
            console.log(`\n📋 ${name}:`);
            try {
                const result = test.call(this);
                console.log(`✅ ${name}: PASSED`);
                return { name, status: 'PASSED', result };
            } catch (error) {
                console.error(`❌ ${name}: FAILED`, error);
                return { name, status: 'FAILED', error: error.message };
            }
        });
        
        console.log('\n==========================================');
        console.log('🏁 Test Results Summary:');
        results.forEach(({ name, status }) => {
            console.log(`${status === 'PASSED' ? '✅' : '❌'} ${name}: ${status}`);
        });
        
        const passedTests = results.filter(r => r.status === 'PASSED').length;
        const totalTests = results.length;
        
        console.log(`\n📊 Score: ${passedTests}/${totalTests} tests passed`);
        
        if (passedTests === totalTests) {
            console.log('🎉 All tests passed! Double spinner issue should be fixed.');
        } else if (passedTests >= totalTests - 1) {
            console.log('✅ Most tests passed! Implementation looks good.');
        } else {
            console.log('⚠️ Some tests failed. Please check the implementation.');
        }
        
        return results;
    },
    
    // Quick double spinner test
    quickDoubleSpinnerTest: function() {
        console.log('⚡ Quick Double Spinner Test:');
        
        if (window.ECUSpinnerControl) {
            console.log('- Testing rapid show/hide...');
            
            // Show spinner multiple times rapidly
            window.ECUSpinnerControl.show(300);
            
            setTimeout(() => {
                window.ECUSpinnerControl.show(300);
            }, 100);
            
            setTimeout(() => {
                console.log('- If you saw only one spinner, the fix is working! ✅');
            }, 600);
        }
        
        return true;
    }
};

// Otomatik test çalıştırma (sayfa yüklendiğinde)
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 DOM Content Loaded - Starting automatic tests...');
    
    // 3 saniye bekle ki tüm scriptler yüklensin
    setTimeout(() => {
        BackButtonDebugger.runAllTests();
    }, 3000);
});

// Global olarak erişilebilir yap
window.BackButtonDebugger = BackButtonDebugger;

// Manuel test için talimatlar
console.log(`
🔧 MANUAL TEST INSTRUCTIONS:
===============================================

1. Open browser console (F12)

2. Run automatic tests:
   BackButtonDebugger.runAllTests()

3. Test specific components:
   BackButtonDebugger.testSpinnerElement()
   BackButtonDebugger.testDoubleSpinner()
   BackButtonDebugger.quickDoubleSpinnerTest()

4. Test back button manually:
   - Navigate to different pages
   - Use browser back button
   - Check if spinner shows ONLY ONCE

5. Monitor console for debug messages:
   - 🔄 BACK/FORWARD BUTTON DETECTED
   - 📦 PAGE SHOW EVENT
   - 📦 Skipping spinner - not a cache navigation

6. Quick test for double spinner:
   BackButtonDebugger.quickDoubleSpinnerTest()

===============================================
`);
