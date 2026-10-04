(function () {
    'use strict';

    window.copyToClipboard = function (text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Token copied to clipboard!');
    };
}());