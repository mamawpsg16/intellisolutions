document.addEventListener('DOMContentLoaded', function() {
    const uploadTypeSelect = document.getElementById('upload-type');
    const uploadButton = document.getElementById('upload-button');

    function updateButtonState() {
        if (uploadTypeSelect.value === '') {
            uploadButton.disabled = true;
        } else {
            uploadButton.disabled = false;
        }
    }

    // Initial check in case a type is pre-selected
    updateButtonState();

    // Add event listener to update button state on change
    uploadTypeSelect.addEventListener('change', updateButtonState);
});