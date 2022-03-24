//
$( document ).ready(function() {
    var form = $("#example-form");

    form.steps({
        headerTag: "h6",
        bodyTag: "section",
        transitionEffect: "fade",
        titleTemplate: '<span class="step">#index#</span> #title#'
    });
});