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
        this.serverTimeAtLoad = null;
        this.localTimeAtLoad = null;
        this.lastTickAt = null;
        this.lastResyncAt = 0;
        this.resyncInFlight = false;
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

            // Resync whenever the tab becomes visible — JS execution may have been
            // paused (background throttling, sleep, BFCache freeze) and the anchor
            // could be stale. Cheap (debounced + ~100B response) and removes the
            // need to reason about which browsers pause when.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.resync();
            });

            // BFCache restore: JS state was preserved but the page was frozen for
            // an unknown duration. event.persisted distinguishes a true restore
            // from the synthetic first-load pageshow.
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) this.resync();
            });
        }
    }

    /**
     * Fetches the current server time and re-anchors the ticker. Triggered when
     * we have reason to believe `serverTimeAtLoad` may be stale (visibility
     * change, BFCache restore, suspicious clock jump in tick()).
     *
     * @private
     */
    async resync() {
        if (this.tickerServerElement === null) return;
        if (this.resyncInFlight) return;
        if (Date.now() - this.lastResyncAt < 2000) return;

        this.resyncInFlight = true;
        try {
            const response = await fetch('/api/v1/time', { cache: 'no-store' });
            if (!response.ok) return;

            // Prefer the response's Date header — it's emitted by nginx for free
            // in the production short-circuit setup, and is RFC-1123 seconds
            // precision (enough for HH:MM:SS).
            const dateHeader = response.headers.get('Date');
            let serverMs = dateHeader ? Date.parse(dateHeader) : NaN;

            if (isNaN(serverMs)) {
                const body = await response.json();
                if (!body || typeof body.t !== 'string') return;
                serverMs = new Date('1970-01-01T' + body.t + 'Z').getTime();
                if (isNaN(serverMs)) return;
            }

            this.serverTimeAtLoad = serverMs;
            this.localTimeAtLoad = Date.now();
            this.nextHour = null;
            this.lastResyncAt = Date.now();
            this.tick();
        } catch (e) {
            // Network errors are non-fatal — keep the existing offset.
        } finally {
            this.resyncInFlight = false;
        }
    }

    /**
     * Ticks the ticker.
     *
     * @private
     */
    tick() {
        // If the gap since the last tick is well beyond the 1Hz hidden-tab
        // throttle floor, JS execution was likely suspended and our anchor
        // may be stale. Trigger a resync. Threshold is 1500ms so a healthy
        // throttled tab (which fires at ~1000ms) doesn't false-positive.
        if (this.lastTickAt !== null && Date.now() - this.lastTickAt > 1500) {
            this.resync();
        }

        let currentTime = null;

        if (this.tickerServerElement !== null) {
            const elapsed = Date.now() - this.localTimeAtLoad;
            currentTime = new Date(this.serverTimeAtLoad + elapsed);
            this.tickerServerElement.innerHTML = Ticker.hms(Ticker.utc(currentTime));

            if (this.nextHour == null) {
                this.nextHour = Ticker.computeNextHour(currentTime);
            }

            if (currentTime >= this.nextHour && this.tickerServerElement.dataset.roundActive === '1') {
                document.documentElement.classList.add('hourchange');
            }
        }

        if (this.tickerNextRoundElement !== null){
            const diffDate = (this.nextRoundStartDate - new Date());

            if (diffDate > 0) {
                this.tickerNextRoundElement.innerHTML = Ticker.hms(diffDate);
            }
        } else if (this.tickerNextHourElement !== null && currentTime !== null) {
            const nextHour = Ticker.computeNextHour(currentTime);
            const diffDate = (nextHour - currentTime);
            this.tickerNextHourElement.innerHTML = Ticker.hms(diffDate);

            if (this.nextHour == null) {
                this.nextHour = nextHour;
            }

            if (currentTime >= this.nextHour) {
                document.documentElement.classList.add('hourchange');
            }
        }

        this.lastTickAt = Date.now();
    }

    /**
     * Returns the next UTC hour boundary after the given time.
     *
     * @param {Date} currentTime
     * @returns {Date}
     * @private
     */
    static computeNextHour(currentTime) {
        const nextHour = new Date(currentTime.getTime());
        nextHour.setUTCHours(currentTime.getUTCHours() + 1);
        nextHour.setUTCMinutes(0);
        nextHour.setUTCSeconds(0);
        nextHour.setUTCMilliseconds(0);
        return nextHour;
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
