'use strict';

class Ticker {
    /**
     * Ticker constructor.
     */
    constructor() {
        this.tickerServerElement = null;
        this.tickerNextHourElement = null;
        this.tickerNextRoundElement = null;
        this.nextRoundStartDate = null;
        this.nextHour = null;
    }

    /**
     * Starts the ticker.
     */
    start() {
        this.tickerServerElement = document.getElementById('ticker-server');
        this.tickerNextHourElement = document.getElementById('ticker-next-tick');
        this.tickerNextRoundElement = document.getElementById('ticker-next-round');

        if (this.tickerNextRoundElement !== null) {
            this.nextRoundStartDate = new Date(this.tickerNextRoundElement.dataset.value);
        }

        if (this.tickerServerElement !== null) {
            const currentServerTime = this.tickerServerElement.innerHTML;
            // Add 500ms to compensate for network/rendering delay between server timestamp and JS init
            this.serverTimeAtLoad = new Date('1970-01-01T' + currentServerTime + 'Z').getTime() + 500;
            this.localTimeAtLoad = Date.now();
        }

        // Only tick if the ticker element is visible; i.e. not on the homepage
        if (this.tickerServerElement !== null || this.tickerNextRoundElement) {
            // Align first tick to the next second boundary
            const msUntilNextSecond = 1000 - (Date.now() % 1000);
            setTimeout(() => {
                this.tick();
                setInterval(() => this.tick(), 1000);
            }, msUntilNextSecond);

            // Resync immediately when tab becomes visible
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.tick();
            });
        }
    }

    /**
     * Ticks the ticker.
     *
     * @private
     */
    tick() {
        let currentTime = null;

        if (this.tickerServerElement !== null) {
            const elapsed = Date.now() - this.localTimeAtLoad;
            currentTime = new Date(this.serverTimeAtLoad + elapsed);
            this.tickerServerElement.innerHTML = Ticker.hms(Ticker.utc(currentTime));

            if (this.nextHour == null) {
                this.nextHour = new Date(currentTime.toString());
                this.nextHour.setUTCHours(currentTime.getUTCHours() + 1);
                this.nextHour.setUTCMinutes(0);
                this.nextHour.setUTCSeconds(0);
            }

            if (currentTime >= this.nextHour && this.tickerServerElement.dataset.roundActive === '1') {
                var htmlElement = document.getElementsByTagName("html")[0];
                htmlElement.classList.add("hourchange");
            }
        }

        if (this.tickerNextRoundElement !== null){
            const diffDate = (this.nextRoundStartDate - new Date());

            if (diffDate > 0) {
                this.tickerNextRoundElement.innerHTML = Ticker.hms(diffDate);
            }
        } else if (this.tickerNextHourElement !== null && currentTime !== null) {
            const nextHour = new Date(currentTime.toString());
            nextHour.setUTCHours(currentTime.getUTCHours() + 1);
            nextHour.setUTCMinutes(0);
            nextHour.setUTCSeconds(0);

            const diffDate = (nextHour - currentTime);
            this.tickerNextHourElement.innerHTML = Ticker.hms(diffDate);

            if (this.nextHour == null) {
                this.nextHour = nextHour;
            }

            if (currentTime >= this.nextHour) {
                var htmlElement = document.getElementsByTagName("html")[0];
                htmlElement.classList.add("hourchange");
            }
        }
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


            let tempValue = value;
            hours = Math.floor(tempValue / 3600);
            tempValue -= hours * 3600;
            minutes = Math.floor(tempValue / 60);
            tempValue -= minutes * 60;
            seconds = Math.floor(tempValue);
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
        return value.toString().padStart(2, '0');
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

export default new Ticker;
