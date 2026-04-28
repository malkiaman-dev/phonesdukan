document.addEventListener("DOMContentLoaded", function () {
    // Ensure the edit modal is hidden when the page loads
    document.getElementById("editModal").style.display = "none";

    // Function to open the Edit Metadata Modal
    function openEditModal(imageId, title, altText, caption, description) {
        document.getElementById('editImageId').value = imageId;
        document.getElementById('editTitle').value = title;
        document.getElementById('editAltText').value = altText;
        document.getElementById('editCaption').value = caption;
        document.getElementById('editDescription').value = description;
        document.getElementById('editModal').style.display = "block";
    }

    // Function to close the Edit Metadata Modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = "none";
    }

    // Function to update image metadata
    function updateImageMetadata() {
        const imageId = document.getElementById('editImageId').value;
        const title = document.getElementById('editTitle').value;
        const altText = document.getElementById('editAltText').value;
        const caption = document.getElementById('editCaption').value;
        const description = document.getElementById('editDescription').value;

        fetch('update_image_metadata.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'update',
                image_id: imageId,
                title: title,
                alt_text: altText,
                caption: caption,
                description: description
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeEditModal(); // Close modal after update
                location.reload(); // Refresh to see updated metadata
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Attach functions to the window object so they can be called in HTML
    window.openEditModal = openEditModal;
    window.closeEditModal = closeEditModal;
    window.updateImageMetadata = updateImageMetadata;
});


// For deleting image
document.getElementById('deleteImageBtn').addEventListener('click', function () {
    const imageId = document.getElementById('editImageId').value;

    if (confirm("Are you sure you want to delete this image?")) {
        fetch('update_image_metadata.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', image_id: imageId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeEditModal(); // Close modal after deletion
                location.reload(); // Refresh to update the image list
            } else {
                alert("Error: " + data.message);
            }
        });
    }
});
