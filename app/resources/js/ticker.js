'use strict';

class Ticker {
    /**
     * Ticker constructor.
     */
    constructor() {
        this.tickerServerElement = null;
        this.tickerNextHourElement = null;
    }

    /**
     * Starts the ticker.
     */
    start() {
        this.tickerServerElement = document.getElementById('ticker-server');
        this.tickerNextHourElement = document.getElementById('ticker-next-tick');

        // Only tick if the ticker element is visible; i.e. not on the homepage
        if (this.tickerServerElement !== null) {
            const self = this;
            setInterval(() => self.tick(), 1000);
        }
    }

    /**
     * Ticks the ticker.
     *
     * @private
     */
    tick() {
        const currentServerTime = this.tickerServerElement.innerHTML;
        const currentTime = new Date('1970-01-01T' + currentServerTime + 'Z');
        currentTime.setUTCSeconds(currentTime.getUTCSeconds() + 1);

        const nextHour = new Date(currentTime.toString());
        nextHour.setUTCHours(currentTime.getUTCHours() + 1);
        nextHour.setMinutes(0);
        nextHour.setSeconds(0);

        const diffDate = (nextHour - currentTime);

        this.tickerServerElement.innerHTML = Ticker.hms(Ticker.utc(currentTime));
        this.tickerNextHourElement.innerHTML = Ticker.hms(diffDate);
    }

    /**
     * Formats a Date or numeric value.
     *
     * @param {Date|number} value
     * @return {string}
     * @private
     */
    static hms(value) {
        let hours = 0;
        let minutes = 0;
        let seconds = 0;

        if (value instanceof Date) {
            hours = value.getHours();
            minutes = value.getMinutes();
            seconds = value.getSeconds();

        } else if (typeof value === 'number') {
            value /= 1000;
            seconds = value % 60;
            minutes = (value - seconds) / 60;
            hours = (value - (minutes * 60) - seconds) / 60;
        }

        return (
            Ticker.pad(hours) +
            ':' + Ticker.pad(minutes) +
            ':' + Ticker.pad(seconds)
        );
    }

    /**
     * Left-pads a numeric value with two zeroes.
     *
     * @param {number} value
     * @returns {string}
     * @private
     */
    static pad(value) {
        return ('00' + value).slice(-2);
    }

    /**
     * Converts a Date into UTC.
     *
     * @param {Date} date
     * @returns {Date}
     * @private
     */
    static utc(date) {
        return new Date(
            date.getUTCFullYear(),
            date.getUTCMonth(),
            date.getUTCDate(),
            date.getUTCHours(),
            date.getUTCMinutes(),
            date.getUTCSeconds()
        );
    }
}

module.exports = new Ticker;
