(function ($) {
    "use strict";

    var storageKey = "breem-admin-sidebar-collapsed";
    var desktopQuery = window.matchMedia("(min-width: 992px)");

    function isDesktop() {
        return desktopQuery.matches;
    }

    function closeMobileSidebar() {
        if (!isDesktop()) {
            document.body.classList.remove("collapsed");
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

        $(".collapseSidebar").on("click", function (event) {
            event.preventDefault();
            $body.toggleClass("collapsed");

            if (isDesktop()) {
                window.localStorage.setItem(storageKey, $body.hasClass("collapsed") ? "true" : "false");
            }
        });

        $(".breem-menu a:not([data-toggle='collapse'])").on("click", closeMobileSidebar);

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
        });

        window.setTimeout(function () {
            $(".breem-alerts .alert[data-auto-dismiss='true']").alert("close");
        }, 6000);
    });
})(window.jQuery);
