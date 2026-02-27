<style>
    /* Split layout */
    .doc-split {
        display: flex;
        flex-direction: row;
        gap: 10px;
        align-items: stretch;
        min-height: 70vh;
    }
    .doc-panel {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    /* Desktop default: 70% document (left), 30% minuting (right) */
    .panel-left { flex: 0 0 70%; }
    .panel-right { flex: 0 0 30%; }
    .panel-header { padding: 10px 12px; border-bottom: 1px solid #eee; background: #f8f9fa; }
    
    .panel-body { 
        flex: 1 1 auto; 
        overflow: auto; 
        position: relative; /* For sticky actions */
    }
    .panel-body.scroll-sync { scroll-behavior: smooth; }

    /* Resizer */
    .resizer {
        width: 8px;
        cursor: col-resize;
        background: linear-gradient(to right, transparent 0, rgba(0,0,0,0.08) 50%, transparent 100%);
        border-radius: 4px;
    }

    /* Sticky Actions at bottom of left panel */
    .panel-left .sticky-actions {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.06);
        z-index: 2;
        padding: 10px;
    }

    .message-header:hover {
        background-color: #f8f9fa;
    }
    
    .message-bubble { 
        border-left: 3px solid #0d6efd; 
    }

    /* Responsive: stack panels vertically; focus document for readability */
    @media (max-width: 992px) {
        .doc-split { flex-direction: column; }
        .panel-left, .panel-right { flex: 0 0 auto; }
        /* Tablet: give generous space to the document */
        .panel-left .panel-body { height: 85vh; }
        /* Hide resizer on small screens */
        .resizer { display: none; }
    }

    /* Mobile: make document viewer near full-screen for readability */
    @media (max-width: 576px) {
        .panel-header { padding: 8px 10px; }
        .panel-left .panel-body { height: 92vh; }
        .panel-left .sticky-actions { padding: 0.75rem; }
        #pdfPreview { width: 100%; height: 100%; display: block; }
    }
</style>
