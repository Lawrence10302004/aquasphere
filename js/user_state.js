/**
 * User State Management Utility
 * Handles persistence to database (Railway) with localStorage fallback for local development
 */

const UserState = {
    // Check if we're in production (Railway) or local
    isProduction: () => {
        return window.location.hostname !== 'localhost' && 
               window.location.hostname !== '127.0.0.1' &&
               !window.location.hostname.startsWith('192.168.');
    },

    // Load state from server, fallback to localStorage
    async loadState() {
        try {
            const resp = await fetch('api/user_state_get.php');
            if (resp.ok) {
                const data = await resp.json();
                if (data.success) {
                    // Hydrate localStorage from server (for compatibility)
                    if (data.cart) localStorage.setItem('cart', JSON.stringify(data.cart));
                    if (data.delivery_address !== undefined) {
                        localStorage.setItem('deliveryAddress', JSON.stringify(data.delivery_address));
                    }
                    if (data.selected_cart_items) {
                        localStorage.setItem('selectedCartItems', JSON.stringify(data.selected_cart_items));
                    }
                    if (data.checkout_items) {
                        localStorage.setItem('checkoutItems', JSON.stringify(data.checkout_items));
                    }
                    if (data.pending_order_id) {
                        localStorage.setItem('pendingOrderId', data.pending_order_id);
                    }
                    if (data.pending_checkout_items) {
                        localStorage.setItem('pendingCheckoutItems', JSON.stringify(data.pending_checkout_items));
                    }
                    if (data.payment_redirect_time) {
                        localStorage.setItem('paymentRedirectTime', data.payment_redirect_time);
                    }
                    if (data.paymongo_checkout_url) {
                        localStorage.setItem('paymongoCheckoutUrl', data.paymongo_checkout_url);
                    }
                    if (data.payment_page_url) {
                        localStorage.setItem('paymentPageUrl', data.payment_page_url);
                    }
                    if (data.pending_cancellation_orders) {
                        localStorage.setItem('pendingCancellationOrders', JSON.stringify(data.pending_cancellation_orders));
                    }
                    return data;
                }
            }
        } catch (e) {
            console.warn('Failed to load state from server, using localStorage:', e);
        }
        
        // Fallback to localStorage
        return {
            success: true,
            cart: JSON.parse(localStorage.getItem('cart') || '[]'),
            delivery_address: JSON.parse(localStorage.getItem('deliveryAddress') || 'null'),
            selected_cart_items: JSON.parse(localStorage.getItem('selectedCartItems') || '[]'),
            checkout_items: JSON.parse(localStorage.getItem('checkoutItems') || '[]'),
            pending_order_id: localStorage.getItem('pendingOrderId') || null,
            pending_checkout_items: JSON.parse(localStorage.getItem('pendingCheckoutItems') || 'null'),
            payment_redirect_time: localStorage.getItem('paymentRedirectTime') || null,
            paymongo_checkout_url: localStorage.getItem('paymongoCheckoutUrl') || null,
            payment_page_url: localStorage.getItem('paymentPageUrl') || null,
            pending_cancellation_orders: JSON.parse(localStorage.getItem('pendingCancellationOrders') || '[]')
        };
    },

    // Save state to server (and localStorage for local dev)
    async saveState(stateUpdates) {
        // Always update localStorage first (for immediate UI updates and local dev)
        if (stateUpdates.cart !== undefined) {
            localStorage.setItem('cart', JSON.stringify(stateUpdates.cart));
        }
        if (stateUpdates.delivery_address !== undefined) {
            if (stateUpdates.delivery_address === null) {
                localStorage.removeItem('deliveryAddress');
            } else {
                localStorage.setItem('deliveryAddress', JSON.stringify(stateUpdates.delivery_address));
            }
        }
        if (stateUpdates.selected_cart_items !== undefined) {
            if (stateUpdates.selected_cart_items.length === 0) {
                localStorage.removeItem('selectedCartItems');
            } else {
                localStorage.setItem('selectedCartItems', JSON.stringify(stateUpdates.selected_cart_items));
            }
        }
        if (stateUpdates.checkout_items !== undefined) {
            if (stateUpdates.checkout_items.length === 0) {
                localStorage.removeItem('checkoutItems');
            } else {
                localStorage.setItem('checkoutItems', JSON.stringify(stateUpdates.checkout_items));
            }
        }
        if (stateUpdates.pending_order_id !== undefined) {
            if (stateUpdates.pending_order_id === null) {
                localStorage.removeItem('pendingOrderId');
            } else {
                localStorage.setItem('pendingOrderId', stateUpdates.pending_order_id);
            }
        }
        if (stateUpdates.pending_checkout_items !== undefined) {
            if (stateUpdates.pending_checkout_items === null) {
                localStorage.removeItem('pendingCheckoutItems');
            } else {
                localStorage.setItem('pendingCheckoutItems', JSON.stringify(stateUpdates.pending_checkout_items));
            }
        }
        if (stateUpdates.payment_redirect_time !== undefined) {
            if (stateUpdates.payment_redirect_time === null) {
                localStorage.removeItem('paymentRedirectTime');
            } else {
                localStorage.setItem('paymentRedirectTime', stateUpdates.payment_redirect_time);
            }
        }
        if (stateUpdates.paymongo_checkout_url !== undefined) {
            if (stateUpdates.paymongo_checkout_url === null) {
                localStorage.removeItem('paymongoCheckoutUrl');
            } else {
                localStorage.setItem('paymongoCheckoutUrl', stateUpdates.paymongo_checkout_url);
            }
        }
        if (stateUpdates.payment_page_url !== undefined) {
            if (stateUpdates.payment_page_url === null) {
                localStorage.removeItem('paymentPageUrl');
            } else {
                localStorage.setItem('paymentPageUrl', stateUpdates.payment_page_url);
            }
        }
        if (stateUpdates.pending_cancellation_orders !== undefined) {
            if (stateUpdates.pending_cancellation_orders.length === 0) {
                localStorage.removeItem('pendingCancellationOrders');
            } else {
                localStorage.setItem('pendingCancellationOrders', JSON.stringify(stateUpdates.pending_cancellation_orders));
            }
        }

        // Save to server (async, don't block)
        try {
            await fetch('api/user_state_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(stateUpdates)
            });
        } catch (e) {
            console.warn('Failed to save state to server (will retry on next change):', e);
        }
    },

    // Debounced save (for frequent updates like cart changes)
    debouncedSave: (() => {
        let timer = null;
        return function(stateUpdates, delay = 700) {
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => {
                UserState.saveState(stateUpdates);
                timer = null;
            }, delay);
        };
    })(),

    // Convenience getters (read from localStorage, which is synced from server)
    getCart: () => JSON.parse(localStorage.getItem('cart') || '[]'),
    getDeliveryAddress: () => {
        const addr = localStorage.getItem('deliveryAddress');
        return addr ? JSON.parse(addr) : null;
    },
    getSelectedCartItems: () => JSON.parse(localStorage.getItem('selectedCartItems') || '[]'),
    getCheckoutItems: () => JSON.parse(localStorage.getItem('checkoutItems') || '[]'),
    getPendingOrderId: () => localStorage.getItem('pendingOrderId') || null,
    getPendingCheckoutItems: () => {
        const items = localStorage.getItem('pendingCheckoutItems');
        return items ? JSON.parse(items) : null;
    },
    getPaymentRedirectTime: () => localStorage.getItem('paymentRedirectTime') || null,
    getPaymongoCheckoutUrl: () => localStorage.getItem('paymongoCheckoutUrl') || null,
    getPaymentPageUrl: () => localStorage.getItem('paymentPageUrl') || null,
    getPendingCancellationOrders: () => JSON.parse(localStorage.getItem('pendingCancellationOrders') || '[]'),

    // Convenience setters (update both localStorage and server)
    setCart: (cart) => UserState.debouncedSave({ cart }),
    setDeliveryAddress: (address) => UserState.saveState({ delivery_address: address }),
    setSelectedCartItems: (items) => UserState.debouncedSave({ selected_cart_items: Array.isArray(items) ? items : Array.from(items) }),
    setCheckoutItems: (items) => UserState.saveState({ checkout_items: items }),
    setPendingOrderId: (id) => UserState.saveState({ pending_order_id: id }),
    setPendingCheckoutItems: (items) => UserState.saveState({ pending_checkout_items: items }),
    setPaymentRedirectTime: (time) => UserState.saveState({ payment_redirect_time: time }),
    setPaymongoCheckoutUrl: (url) => UserState.saveState({ paymongo_checkout_url: url }),
    setPaymentPageUrl: (url) => UserState.saveState({ payment_page_url: url }),
    setPendingCancellationOrders: (orders) => UserState.saveState({ pending_cancellation_orders: orders }),

    // Clear all state
    clearAll: async () => {
        localStorage.removeItem('cart');
        localStorage.removeItem('deliveryAddress');
        localStorage.removeItem('selectedCartItems');
        localStorage.removeItem('checkoutItems');
        localStorage.removeItem('pendingOrderId');
        localStorage.removeItem('pendingCheckoutItems');
        localStorage.removeItem('paymentRedirectTime');
        localStorage.removeItem('paymongoCheckoutUrl');
        localStorage.removeItem('paymentPageUrl');
        localStorage.removeItem('pendingCancellationOrders');
        
        await UserState.saveState({
            cart: [],
            delivery_address: null,
            selected_cart_items: [],
            checkout_items: [],
            pending_order_id: null,
            pending_checkout_items: null,
            payment_redirect_time: null,
            paymongo_checkout_url: null,
            payment_page_url: null,
            pending_cancellation_orders: []
        });
    }
};

