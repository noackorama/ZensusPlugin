STUDIP.QRCourse = {
    showQR: function () {
        var qr = jQuery("#qr_code")[0];
        if (qr.requestFullscreen) {
            qr.requestFullscreen();
        } else if (qr.msRequestFullscreen) {
            qr.msRequestFullscreen();
        } else if (qr.mozRequestFullScreen) {
            qr.mozRequestFullScreen();
        } else if (qr.webkitRequestFullscreen) {
            qr.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    }
};