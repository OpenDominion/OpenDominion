'use strict';

const resources = JSON.parse('{!! json_encode($resources) !!}');

const update = function update() {
    const currentSource = $("#source").find(":selected");
    const sourceResource = resources[currentSource.val()];
    const $sourceAmount = $("#sourceAmount");
    const sourceAmount = Math.min(parseInt($sourceAmount.val()), sourceResource['max']);
    const currentTarget = $("#target").find(":selected");
    const targetResource = resources[currentTarget.val()];
    const targetAmount = Math.floor(sourceAmount * sourceResource['sell'] * targetResource['buy']);

    $("#sourceResource").text(currentSource.text());
    $("#targetResource").text(currentTarget.text());
    $sourceAmount.attr('max', sourceResource['max']).val(sourceAmount);
    $("#targetAmount").text(targetAmount.toLocaleString());
    $("input.slider").slider('setAttribute', 'max', sourceResource['max']).slider('setValue', sourceAmount);
};

$("input.slider").slider({
    formatter: function (value) {
        return value.toLocaleString();
    }
})
    .on("change", function (slideEvent) {
        $("#sourceAmount").val(slideEvent.value.newValue).change();
    });
$("#sourceAmount").on("change", update);
$("#source").on("change", update);
$("#target").on("change", update);
