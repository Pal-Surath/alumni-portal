// ============================================================
// ALUMNI PORTAL - MAIN JAVASCRIPT
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    // ========================================================
    // 1. MOBILE NAVIGATION MENU
    // ========================================================

    const menuToggle = document.querySelector(".menu-toggle");
    const navMenu = document.querySelector(".nav-menu");

    if (menuToggle && navMenu) {

        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation();

            navMenu.classList.toggle("show");

            const isOpen = navMenu.classList.contains("show");

            menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        // Close menu when clicking a navigation link
        navMenu.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                navMenu.classList.remove("show");
                menuToggle.setAttribute("aria-expanded", "false");
            });
        });

        // Close menu when clicking outside
        document.addEventListener("click", function (event) {
            if (
                navMenu.classList.contains("show") &&
                !navMenu.contains(event.target) &&
                !menuToggle.contains(event.target)
            ) {
                navMenu.classList.remove("show");
                menuToggle.setAttribute("aria-expanded", "false");
            }
        });

        // Close menu when screen becomes large
        window.addEventListener("resize", function () {
            if (window.innerWidth > 768) {
                navMenu.classList.remove("show");
                menuToggle.setAttribute("aria-expanded", "false");
            }
        });
    }


    // ========================================================
    // 2. PASSWORD SHOW / HIDE
    // ========================================================

    const passwordToggles = document.querySelectorAll(".password-toggle");

    passwordToggles.forEach(function (toggle) {

        toggle.addEventListener("click", function () {

            const targetId = toggle.getAttribute("data-target");

            if (!targetId) {
                return;
            }

            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggle.textContent = "Hide";
                toggle.setAttribute("aria-label", "Hide password");
            } else {
                passwordInput.type = "password";
                toggle.textContent = "Show";
                toggle.setAttribute("aria-label", "Show password");
            }
        });

    });


    // ========================================================
    // 3. AUTO DISMISS ALERT MESSAGES
    // ========================================================

    const alerts = document.querySelectorAll("[data-auto-dismiss]");

    alerts.forEach(function (alert) {

        let delay = parseInt(
            alert.getAttribute("data-auto-dismiss"),
            10
        );

        if (isNaN(delay)) {
            delay = 5000;
        }

        setTimeout(function () {

            alert.style.opacity = "0";
            alert.style.transform = "translateY(-5px)";

            setTimeout(function () {
                alert.remove();
            }, 300);

        }, delay);

    });


    // ========================================================
    // 4. CONFIRM DELETE / IMPORTANT ACTION
    // ========================================================

    const confirmElements = document.querySelectorAll(".confirm-action");

    confirmElements.forEach(function (element) {

        element.addEventListener("click", function (event) {

            const message =
                element.getAttribute("data-confirm") ||
                "Are you sure you want to continue?";

            const confirmed = window.confirm(message);

            if (!confirmed) {
                event.preventDefault();
            }

        });

    });


    // ========================================================
    // 5. CURRENT YEAR
    // ========================================================

    const currentYearElements =
        document.querySelectorAll("[data-current-year]");

    currentYearElements.forEach(function (element) {
        element.textContent = new Date().getFullYear();
    });


    // ========================================================
    // 6. PROFILE IMAGE PREVIEW
    // ========================================================

    const imageInputs =
        document.querySelectorAll("[data-image-preview]");

    imageInputs.forEach(function (input) {

        input.addEventListener("change", function () {

            const previewId =
                input.getAttribute("data-image-preview");

            if (!previewId) {
                return;
            }

            const preview =
                document.getElementById(previewId);

            if (!preview) {
                return;
            }

            const file = input.files[0];

            if (!file) {
                return;
            }

            // Check image type
            if (!file.type.startsWith("image/")) {
                alert("Please select a valid image file.");
                input.value = "";
                return;
            }

            // Maximum 5 MB
            const maxSize = 5 * 1024 * 1024;

            if (file.size > maxSize) {
                alert("Image size must be less than 5 MB.");
                input.value = "";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.style.display = "block";
            };

            reader.readAsDataURL(file);

        });

    });


    // ========================================================
    // 7. BASIC CLIENT-SIDE FORM VALIDATION
    // ========================================================

    const validationForms =
        document.querySelectorAll("[data-validate]");

    validationForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            let valid = true;

            const requiredFields =
                form.querySelectorAll("[required]");

            requiredFields.forEach(function (field) {

                field.classList.remove("input-error");

                if (!field.value.trim()) {

                    valid = false;
                    field.classList.add("input-error");

                }

            });


            // Email validation
            const emailFields =
                form.querySelectorAll('input[type="email"]');

            emailFields.forEach(function (field) {

                if (
                    field.value.trim() !== "" &&
                    !isValidEmail(field.value.trim())
                ) {

                    valid = false;
                    field.classList.add("input-error");

                }

            });


            // Password minimum length
            const passwordFields =
                form.querySelectorAll('input[type="password"]');

            passwordFields.forEach(function (field) {

                if (
                    field.value.length > 0 &&
                    field.value.length < 8
                ) {

                    valid = false;
                    field.classList.add("input-error");

                }

            });


            if (!valid) {

                event.preventDefault();

                const firstError =
                    form.querySelector(".input-error");

                if (firstError) {
                    firstError.focus();
                }

                alert(
                    "Please check the highlighted fields and correct the errors."
                );

            }

        });

    });


    // ========================================================
    // 8. EMAIL VALIDATION FUNCTION
    // ========================================================

    function isValidEmail(email) {

        const emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        return emailPattern.test(email);

    }


    // ========================================================
    // 9. REMOVE ERROR STYLE WHEN USER TYPES
    // ========================================================

    const formInputs =
        document.querySelectorAll(
            "input, textarea, select"
        );

    formInputs.forEach(function (input) {

        input.addEventListener("input", function () {
            input.classList.remove("input-error");
        });

        input.addEventListener("change", function () {
            input.classList.remove("input-error");
        });

    });


    // ========================================================
    // 10. PREVENT DOUBLE FORM SUBMISSION
    // ========================================================

    const loadingForms =
        document.querySelectorAll("[data-loading]");

    loadingForms.forEach(function (form) {

        form.addEventListener("submit", function () {

            const submitButton =
                form.querySelector(
                    'button[type="submit"], input[type="submit"]'
                );

            if (!submitButton) {
                return;
            }

            // Small delay so browser can submit normally
            setTimeout(function () {

                submitButton.disabled = true;

                if (submitButton.tagName === "BUTTON") {
                    submitButton.dataset.originalText =
                        submitButton.textContent;

                    submitButton.textContent = "Processing...";
                }

            }, 10);

        });

    });


    // ========================================================
    // 11. PASSWORD MATCH VALIDATION
    // ========================================================

    const passwordForms =
        document.querySelectorAll("[data-password-match]");

    passwordForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const password =
                form.querySelector('[name="password"]');

            const confirmPassword =
                form.querySelector(
                    '[name="confirm_password"]'
                );

            if (!password || !confirmPassword) {
                return;
            }

            if (password.value !== confirmPassword.value) {

                event.preventDefault();

                confirmPassword.classList.add("input-error");

                alert("Passwords do not match.");

                confirmPassword.focus();
            }

        });

    });


    // ========================================================
    // 12. SMOOTH SCROLL FOR INTERNAL LINKS
    // ========================================================

    const internalLinks =
        document.querySelectorAll('a[href^="#"]');

    internalLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                link.getAttribute("href");

            if (!targetId || targetId === "#") {
                return;
            }

            const target =
                document.querySelector(targetId);

            if (!target) {
                return;
            }

            event.preventDefault();

            target.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

        });

    });


    // ========================================================
    // 13. SEARCH INPUT - CLEAR BUTTON
    // ========================================================

    const searchInputs =
        document.querySelectorAll("[data-search-input]");

    searchInputs.forEach(function (input) {

        input.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {
                input.value = "";
                input.focus();
            }

        });

    });


    // ========================================================
    // 14. DISABLE BUTTON AFTER CLICK
    // ========================================================

    const singleClickButtons =
        document.querySelectorAll("[data-single-click]");

    singleClickButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            if (button.dataset.clicked === "true") {
                return;
            }

            button.dataset.clicked = "true";

            setTimeout(function () {
                button.dataset.clicked = "false";
            }, 3000);

        });

    });


    // ========================================================
    // 15. CONSOLE MESSAGE
    // ========================================================

    console.log(
        "Alumni Portal JavaScript loaded successfully."
    );

});

