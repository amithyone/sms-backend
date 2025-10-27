/**
 * Frontend Error Logger
 * Comprehensive error logging for frontend issues
 */

class ErrorLogger {
    constructor() {
        this.apiBaseUrl = '/api';
        this.sessionId = this.generateSessionId();
        this.requestId = this.generateRequestId();
        this.errorQueue = [];
        this.maxRetries = 3;
        this.retryDelay = 1000; // 1 second
        
        this.initializeErrorHandlers();
        this.initializePerformanceMonitoring();
        this.startErrorQueueProcessor();
    }

    /**
     * Initialize global error handlers
     */
    initializeErrorHandlers() {
        // Global JavaScript errors
        window.addEventListener('error', (event) => {
            this.logError({
                type: 'javascript_error',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                stack: event.error ? event.error.stack : null,
                url: window.location.href,
                component: this.getComponentFromStack(event.error),
            });
        });

        // Unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.logError({
                type: 'promise_rejection',
                message: event.reason ? event.reason.message || event.reason : 'Unhandled promise rejection',
                stack: event.reason ? event.reason.stack : null,
                url: window.location.href,
                component: this.getComponentFromStack(event.reason),
            });
        });

        // Network errors
        this.interceptFetch();
        this.interceptXHR();
    }

    /**
     * Initialize performance monitoring
     */
    initializePerformanceMonitoring() {
        // Monitor page load performance
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    this.logPerformance({
                        metric: 'page_load_time',
                        value: perfData.loadEventEnd - perfData.loadEventStart,
                        threshold: 3000, // 3 seconds
                        component: 'page',
                        action: 'load',
                        metadata: {
                            dom_content_loaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
                            first_paint: this.getFirstPaint(),
                            first_contentful_paint: this.getFirstContentfulPaint(),
                        }
                    });
                }
            }, 0);
        });

        // Monitor API response times
        this.monitorApiPerformance();
    }

    /**
     * Log an error
     */
    logError(errorData) {
        const enrichedError = {
            ...errorData,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent,
            url: window.location.href,
            session_id: this.sessionId,
            request_id: this.requestId,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight,
            },
            screen: {
                width: screen.width,
                height: screen.height,
            },
            user_id: this.getUserId(),
            metadata: {
                ...errorData.metadata,
                referrer: document.referrer,
                language: navigator.language,
                platform: navigator.platform,
                cookie_enabled: navigator.cookieEnabled,
                online: navigator.onLine,
            }
        };

        this.addToQueue('logFrontendError', enrichedError);
    }

    /**
     * Log performance issues
     */
    logPerformance(performanceData) {
        const enrichedPerformance = {
            ...performanceData,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent,
            url: window.location.href,
            session_id: this.sessionId,
            request_id: this.requestId,
            user_id: this.getUserId(),
        };

        this.addToQueue('logPerformanceIssue', enrichedPerformance);
    }

    /**
     * Log API usage
     */
    logApiUsage(endpoint, method, duration, statusCode, responseSize) {
        const apiData = {
            endpoint,
            method,
            duration,
            status_code: statusCode,
            response_size: responseSize,
            timestamp: new Date().toISOString(),
            user_id: this.getUserId(),
            session_id: this.sessionId,
            request_id: this.requestId,
        };

        this.addToQueue('logApiUsage', apiData);
    }

    /**
     * Intercept fetch requests
     */
    interceptFetch() {
        const originalFetch = window.fetch;
        const self = this;

        window.fetch = function(...args) {
            const startTime = performance.now();
            const url = args[0];
            const options = args[1] || {};
            const method = options.method || 'GET';

            return originalFetch.apply(this, args)
                .then(response => {
                    const duration = performance.now() - startTime;
                    const responseSize = response.headers.get('content-length') || 0;
                    
                    self.logApiUsage(url, method, duration, response.status, responseSize);

                    // Log API errors
                    if (!response.ok) {
                        self.logError({
                            type: 'api_error',
                            message: `API Error: ${response.status} ${response.statusText}`,
                            url: url,
                            method: method,
                            status_code: response.status,
                            duration: duration,
                            component: 'api',
                            action: method.toLowerCase(),
                        });
                    }

                    return response;
                })
                .catch(error => {
                    const duration = performance.now() - startTime;
                    
                    self.logError({
                        type: 'network_error',
                        message: error.message,
                        url: url,
                        method: method,
                        duration: duration,
                        component: 'api',
                        action: method.toLowerCase(),
                    });

                    throw error;
                });
        };
    }

    /**
     * Intercept XMLHttpRequest
     */
    interceptXHR() {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        const self = this;

        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._method = method;
            this._url = url;
            this._startTime = performance.now();
            return originalOpen.apply(this, [method, url, ...args]);
        };

        XMLHttpRequest.prototype.send = function(...args) {
            const xhr = this;
            const method = xhr._method;
            const url = xhr._url;
            const startTime = xhr._startTime;

            xhr.addEventListener('loadend', () => {
                const duration = performance.now() - startTime;
                const responseSize = xhr.getResponseHeader('content-length') || 0;
                
                self.logApiUsage(url, method, duration, xhr.status, responseSize);

                if (xhr.status >= 400) {
                    self.logError({
                        type: 'api_error',
                        message: `XHR Error: ${xhr.status} ${xhr.statusText}`,
                        url: url,
                        method: method,
                        status_code: xhr.status,
                        duration: duration,
                        component: 'api',
                        action: method.toLowerCase(),
                    });
                }
            });

            xhr.addEventListener('error', () => {
                const duration = performance.now() - startTime;
                
                self.logError({
                    type: 'network_error',
                    message: 'XHR Network Error',
                    url: url,
                    method: method,
                    duration: duration,
                    component: 'api',
                    action: method.toLowerCase(),
                });
            });

            return originalSend.apply(this, args);
        };
    }

    /**
     * Monitor API performance
     */
    monitorApiPerformance() {
        // Monitor long-running operations
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 5000) { // 5 seconds
                    this.logPerformance({
                        metric: 'long_running_operation',
                        value: entry.duration,
                        threshold: 5000,
                        component: entry.name,
                        action: 'operation',
                        metadata: {
                            entry_type: entry.entryType,
                        }
                    });
                }
            }
        });

        observer.observe({ entryTypes: ['measure', 'navigation'] });
    }

    /**
     * Add error to queue for processing
     */
    addToQueue(endpoint, data) {
        this.errorQueue.push({ endpoint, data, retries: 0 });
    }

    /**
     * Process error queue
     */
    startErrorQueueProcessor() {
        setInterval(() => {
            if (this.errorQueue.length > 0) {
                this.processQueue();
            }
        }, 5000); // Process every 5 seconds
    }

    /**
     * Process the error queue
     */
    async processQueue() {
        const queue = [...this.errorQueue];
        this.errorQueue = [];

        for (const item of queue) {
            try {
                await this.sendToServer(item.endpoint, item.data);
            } catch (error) {
                if (item.retries < this.maxRetries) {
                    item.retries++;
                    this.errorQueue.push(item);
                } else {
                    console.error('Failed to log error after max retries:', item);
                }
            }
        }
    }

    /**
     * Send data to server
     */
    async sendToServer(endpoint, data) {
        const response = await fetch(`${this.apiBaseUrl}/errors/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Request-ID': this.requestId,
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
        }

        return response.json();
    }

    /**
     * Get user ID from authentication
     */
    getUserId() {
        // Try to get user ID from various sources
        if (window.user && window.user.id) {
            return window.user.id;
        }
        
        // Check localStorage
        const userData = localStorage.getItem('user');
        if (userData) {
            try {
                const user = JSON.parse(userData);
                return user.id;
            } catch (e) {
                return null;
            }
        }
        
        return null;
    }

    /**
     * Get component name from stack trace
     */
    getComponentFromStack(error) {
        if (!error || !error.stack) {
            return 'unknown';
        }

        const stack = error.stack;
        if (stack.includes('SmsApiService')) return 'SmsApiService';
        if (stack.includes('OrderService')) return 'OrderService';
        if (stack.includes('AuthService')) return 'AuthService';
        if (stack.includes('WalletService')) return 'WalletService';
        
        return 'unknown';
    }

    /**
     * Get first paint time
     */
    getFirstPaint() {
        const paintEntries = performance.getEntriesByType('paint');
        const firstPaint = paintEntries.find(entry => entry.name === 'first-paint');
        return firstPaint ? firstPaint.startTime : null;
    }

    /**
     * Get first contentful paint time
     */
    getFirstContentfulPaint() {
        const paintEntries = performance.getEntriesByType('paint');
        const firstContentfulPaint = paintEntries.find(entry => entry.name === 'first-contentful-paint');
        return firstContentfulPaint ? firstContentfulPaint.startTime : null;
    }

    /**
     * Generate session ID
     */
    generateSessionId() {
        let sessionId = sessionStorage.getItem('error_logger_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('error_logger_session_id', sessionId);
        }
        return sessionId;
    }

    /**
     * Generate request ID
     */
    generateRequestId() {
        return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
}

// Initialize error logger
window.errorLogger = new ErrorLogger();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErrorLogger;
}
