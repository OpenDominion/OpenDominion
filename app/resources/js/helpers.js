'use strict';

/**
 * Formats a certain amount of bytes into a human-readable string.
 *
 * @param {number} bytes
 * @param {number} decimals
 * @returns {string}
 */
function formatBytes(bytes, decimals) {
    if (bytes === 0) {
        return '0 Bytes';
    } else if (bytes === 1) {
        return '1 Byte';
    }

    const k = 1024,
        dm = (decimals || 2),
        sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

module.exports = {
    formatBytes,
};
