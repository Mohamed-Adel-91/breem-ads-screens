(function ($) {
    "use strict";

    var storageKey = "breem-admin-sidebar-collapsed";
    var desktopQuery = window.matchMedia("(min-width: 992px)");

    function isDesktop() {
        return desktopQuery.matches;
    }

    function syncSidebarToggleState($body) {
        var isOpen = isDesktop()
            ? !$body.hasClass("collapsed")
            : $body.hasClass("collapsed");

        $(".collapseSidebar[aria-expanded]").attr("aria-expanded", isOpen ? "true" : "false");
    }

    function closeMobileSidebar() {
        if (!isDesktop()) {
            document.body.classList.remove("collapsed");
            syncSidebarToggleState($("body.breem-admin.vertical"));
        }
    }

    $(function () {
        var $body = $("body.breem-admin.vertical");

        if (!$body.length) {
            return;
        }

        if (isDesktop() && window.localStorage.getItem(storageKey) === "true") {
            $body.addClass("collapsed");
        }

        syncSidebarToggleState($body);

        $(".collapseSidebar").on("click", function (event) {
            event.preventDefault();
            $body.toggleClass("collapsed");
            syncSidebarToggleState($body);

            if (isDesktop()) {
                window.localStorage.setItem(storageKey, $body.hasClass("collapsed") ? "true" : "false");
            }
        });

        $(".breem-menu a:not([data-toggle='collapse'])").on("click", closeMobileSidebar);

        $(document).on("submit", "form[data-confirm-message]", function (event) {
            var message = this.getAttribute("data-confirm-message");

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });

        $(document).on("click", "button[data-confirm-message]", function (event) {
            var message = this.getAttribute("data-confirm-message");

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });

        $(document).on("keydown", function (event) {
            if (event.key === "Escape") {
                closeMobileSidebar();
            }
        });

        $(window).on("resize", function () {
            if (!isDesktop()) {
                $body.removeClass("collapsed");
            } else if (window.localStorage.getItem(storageKey) === "true") {
                $body.addClass("collapsed");
            }

            syncSidebarToggleState($body);
        });

        window.setTimeout(function () {
            $(".breem-alerts .alert[data-auto-dismiss='true']").alert("close");
        }, 6000);
    });
})(window.jQuery);
