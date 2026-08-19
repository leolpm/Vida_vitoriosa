<style>
    .request-feedback-loading {
        cursor: wait !important;
    }

    .request-feedback-spinner {
        flex: 0 0 auto;
    }

    [data-loading-status][hidden] {
        display: none !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .request-feedback-spinner {
            animation: none !important;
        }
    }
</style>
