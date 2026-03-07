// Shim that satisfies `import $ from 'jquery'` inside the Vite module bundle
// by returning the jQuery instance already loaded via the classic <script> tag.
// The classic script runs synchronously before any deferred ES module, so
// window.jQuery is guaranteed to be defined by the time this module evaluates.
export default window.jQuery;
