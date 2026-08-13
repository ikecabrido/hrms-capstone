document.addEventListener('DOMContentLoaded', function () {

    const input = document.getElementById('profileImageInput');
    const preview = document.getElementById('imagePreview');
    const selectedName = document.getElementById('selectedImageName');

    if (!input || !preview) {
        return;
    }

    input.addEventListener('change', function () {

        const file = this.files[0];

        if (!file) {
            return;
        }

        /*
         * Validate image type
         */
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

            alert('Please select a JPG, PNG, or WEBP image.');

            input.value = '';

            return;
        }

        /*
         * Optional 5MB limit
         */
        if (file.size > 5 * 1024 * 1024) {

            alert('Image size must not exceed 5MB.');

            input.value = '';

            return;
        }

        /*
         * Preview image
         */
        const reader = new FileReader();

        reader.onload = function (event) {

            preview.innerHTML = `
                <img
                    src="${event.target.result}"
                    alt="Profile Preview"
                    style="
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    "
                >
            `;

        };

        reader.readAsDataURL(file);


        /*
         * Show selected filename
         */
        if (selectedName) {

            selectedName.style.display = 'flex';

            selectedName.querySelector('span').textContent =
                file.name;

        }

    });

});