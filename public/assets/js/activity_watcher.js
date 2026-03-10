document.addEventListener('DOMContentLoaded', () => {
    const startedAt = Date.now();
    let sentClicks = 0;

    const sendWatch = (payload) => {
        const body = JSON.stringify(payload);

        if (navigator.sendBeacon && payload.eventType === 'time_on_page') {
            navigator.sendBeacon('/api/activity/watch', new Blob([body], { type: 'application/json' }));
            return;
        }

        fetch('/api/activity/watch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
            body
        }).catch(() => {});
    };

    sendWatch({
        eventType: 'watcher_loaded',
        path: window.location.pathname,
        metadata: {
            title: document.title
        }
    });

    document.addEventListener('click', (event) => {
        if (sentClicks >= 12) {
            return;
        }

        const target = event.target instanceof Element ? event.target.closest('a,button') : null;
        if (!target) {
            return;
        }

        sentClicks += 1;

        sendWatch({
            eventType: 'click',
            path: window.location.pathname,
            metadata: {
                tag: target.tagName.toLowerCase(),
                text: (target.textContent || '').trim().slice(0, 80),
                href: target.getAttribute('href') || null
            }
        });
    });

    let scroll50Sent = false;
    window.addEventListener('scroll', () => {
        if (scroll50Sent) {
            return;
        }

        const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (documentHeight <= 0) {
            return;
        }

        const ratio = (window.scrollY / documentHeight) * 100;
        if (ratio >= 50) {
            scroll50Sent = true;
            sendWatch({
                eventType: 'scroll_50',
                path: window.location.pathname,
                metadata: { ratio: 50 }
            });
        }
    }, { passive: true });

    const onLeave = () => {
        const duration = Date.now() - startedAt;
        sendWatch({
            eventType: 'time_on_page',
            path: window.location.pathname,
            durationMs: duration,
            metadata: {
                title: document.title
            }
        });
    };

    window.addEventListener('beforeunload', onLeave);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            onLeave();
        }
    });
});
