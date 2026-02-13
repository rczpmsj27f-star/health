/**
 * PDF Share Module
 * Handles sharing PDFs via Capacitor Share (for native apps) or Web Share API (for web)
 */

const PdfShare = {
    /**
     * Check if Capacitor Share is available (for native apps)
     */
    isCapacitorShareAvailable: function() {
        return !!(window.Capacitor && window.Capacitor.isNativePlatform && 
                  window.Capacitor.Plugins && window.Capacitor.Plugins.Share);
    },

    /**
     * Check if Web Share API is available (for web browsers)
     */
    isWebShareAvailable: function() {
        return !!(navigator.share && navigator.canShare);
    },

    /**
     * Save PDF to device using Capacitor Filesystem
     */
    savePdfToDevice: async function(filename, base64Data) {
        try {
            if (!window.Capacitor || !window.Capacitor.Plugins || !window.Capacitor.Plugins.Filesystem) {
                throw new Error('Capacitor Filesystem not available');
            }

            const Filesystem = window.Capacitor.Plugins.Filesystem;

            const result = await Filesystem.writeFile({
                path: filename,
                data: base64Data,
                directory: 'CACHE', // Use string value for Capacitor 5
                recursive: true
            });

            return result.uri;
        } catch (error) {
            console.error('Error saving PDF to device:', error);
            throw error;
        }
    },

    /**
     * Share PDF using Capacitor Share (native apps)
     */
    shareViaCapacitor: async function(filename, base64Data, title = 'Health Tracker PDF') {
        try {
            const Share = window.Capacitor.Plugins.Share;
            const uri = await this.savePdfToDevice(filename, base64Data);

            await Share.share({
                title: title,
                text: 'Share your Health Tracker report',
                files: [uri],
                dialogTitle: 'Share PDF'
            });

            return { success: true, method: 'capacitor' };
        } catch (error) {
            console.error('Error sharing via Capacitor:', error);
            throw error;
        }
    },

    /**
     * Share PDF using Web Share API (web browsers)
     */
    shareViaWebApi: async function(filename, base64Data, title = 'Health Tracker PDF') {
        try {
            // Convert base64 to blob
            const blob = this.base64ToBlob(base64Data, 'application/pdf');
            const file = new File([blob], filename, { type: 'application/pdf' });

            // Check if sharing files is supported
            if (navigator.canShare && !navigator.canShare({ files: [file] })) {
                throw new Error('Sharing files is not supported on this browser');
            }

            await navigator.share({
                title: title,
                text: 'Share your Health Tracker report',
                files: [file]
            });

            return { success: true, method: 'webapi' };
        } catch (error) {
            console.error('Error sharing via Web API:', error);
            throw error;
        }
    },

    /**
     * Convert base64 to Blob
     */
    base64ToBlob: function(base64, mimeType) {
        const byteCharacters = atob(base64);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        return new Blob([byteArray], { type: mimeType });
    },

    /**
     * Download PDF (fallback when sharing is not available)
     */
    downloadPdf: function(filename, base64Data) {
        try {
            const blob = this.base64ToBlob(base64Data, 'application/pdf');
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            return { success: true, method: 'download' };
        } catch (error) {
            console.error('Error downloading PDF:', error);
            throw error;
        }
    },

    /**
     * Main share function - automatically selects the best sharing method
     */
    sharePdf: async function(filename, base64Data, title = 'Health Tracker PDF') {
        try {
            // Prioritize Capacitor Share for native apps
            if (this.isCapacitorShareAvailable()) {
                return await this.shareViaCapacitor(filename, base64Data, title);
            }
            
            // Try Web Share API for modern browsers
            if (this.isWebShareAvailable()) {
                return await this.shareViaWebApi(filename, base64Data, title);
            }
            
            // Fallback to download
            return this.downloadPdf(filename, base64Data);
        } catch (error) {
            console.error('Error sharing PDF:', error);
            // If sharing fails, fallback to download
            try {
                return this.downloadPdf(filename, base64Data);
            } catch (downloadError) {
                console.error('Error downloading PDF:', downloadError);
                throw new Error('Unable to share or download PDF: ' + error.message);
            }
        }
    }
};

// Make available globally
if (typeof window !== 'undefined') {
    window.PdfShare = PdfShare;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PdfShare;
}
