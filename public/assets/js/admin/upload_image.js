document.addEventListener("DOMContentLoaded", function () {
    console.log("JavaScript Loaded"); // Debugging

    let uploadModal = document.getElementById("uploadModal");
    let openUploadBtn = document.getElementById("openUploadModal"); // Corrected ID
    let closeUploadBtn = document.getElementById("closeUploadModal");

    if (!uploadModal || !openUploadBtn || !closeUploadBtn) {
        console.error("Modal elements not found");
        return;
    }

    // Function to open the Upload Image Modal
    function openUploadModal() {
        console.log("Upload Button Clicked"); // Debugging
        uploadModal.style.display = "block";
    }

    // Function to close the Upload Image Modal
    function closeUploadModal() {
        console.log("Close Button Clicked"); // Debugging
        uploadModal.style.display = "none";
    }

    // Attach event listeners
    openUploadBtn.onclick = openUploadModal;
    closeUploadBtn.onclick = closeUploadModal;

    // Close modal when clicking outside of it
    window.onclick = function (event) {
        if (event.target === uploadModal) {
            console.log("Clicked Outside Modal"); // Debugging
            closeUploadModal();
        }
    };

    // Attach functions to the window object so they can be called in HTML
    window.openUploadModal = openUploadModal;
    window.closeUploadModal = closeUploadModal;
});