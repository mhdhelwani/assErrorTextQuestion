$().ready(function () {
    $("#error_type").change(function (e) {
        if ($(this).val() == "C") {
            $("#textsize").val(130);
        } else {
            $("#textsize").val(100);
        }
    });

    $('div.errortextquestion a').on('click', function(e) {
        var $elm = $(this);

        $elm.toggleClass('sel');

        var context  = $elm.closest('.errortextquestion');
        var selected = [];
        context.find('a').each(function(i) {
            if ($(this).hasClass('sel')) {
                selected.push($(this).attr("position"));
            }
        });
        context.find('input[type=hidden]').val(selected.join(','));

        e.preventDefault();
        e.stopPropagation();
    });
    var $info_text_direction = $("#errortext").next().css("direction");
    var $info_text_text_align = $("#errortext").next().css("text-align");

    $("#errortext").parent().attr("dir",  $("#text_direction").val());
    $("#errortext").next().css("direction", $info_text_direction);
    $("#errortext").next().css("text-align", $info_text_text_align);

    $("#text_direction").change(function (e) {
        $("#errortext").parent().attr("dir", $(this).val());
    });

    $("#errortext").change(function (e) {
        $("#is_error_text_changed").val("1");
    });
});