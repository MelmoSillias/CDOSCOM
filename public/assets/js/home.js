document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    const scrollTopButton = document.getElementById('scroll-top');

    window.addEventListener('scroll', () => {
        if (!scrollTopButton) {
            return;
        }

        if (window.scrollY > 300) {
            scrollTopButton.classList.add('active');
        } else {
            scrollTopButton.classList.remove('active');
        }
    });

    if (scrollTopButton) {
        scrollTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', (e) => {
            const targetId = anchor.getAttribute('href');
            const targetElement = targetId ? document.querySelector(targetId) : null;

            if (!targetElement) {
                return;
            }

            e.preventDefault();
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth',
            });

            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    });

    const animateOnScroll = () => {
        document.querySelectorAll('.animate-fadeIn').forEach((element) => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;

            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    window.addEventListener('load', animateOnScroll);
    animateOnScroll();

    const hideLoader = () => {
        const loader = document.getElementById('loader');

        if (!loader) {
            return;
        }

        loader.classList.add('hidden');

        setTimeout(() => {
            const elements = document.querySelectorAll('.animate-fadeIn');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            });
        }, 100);
    };

    // Initialize Flowbite components (carousels, etc.) in both classic and Turbo navigations.
    const bootFlowbite = () => {
        if (typeof initFlowbite === 'function') {
            initFlowbite();
        }
    };

    bootFlowbite();
    window.addEventListener('load', bootFlowbite);
    document.addEventListener('turbo:load', bootFlowbite);
    document.addEventListener('turbo:render', bootFlowbite);

    // Hide loader on full load, Turbo renders, and after a short delay fallback.
    window.addEventListener('load', hideLoader);
    document.addEventListener('turbo:load', hideLoader);
    document.addEventListener('turbo:render', hideLoader);
    requestAnimationFrame(hideLoader);
    setTimeout(hideLoader, 1200);
});
