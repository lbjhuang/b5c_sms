/**
 * 弹出层显示到中间
 *
 */
function centerModals() {
    $('#modal-demo').each(function(i) {
        var $clone = $(this).clone().css('display', 'block').appendTo('body'); var top = Math.round(($clone.height() - $clone.find('.modal-content').height()) / 2);
        top = top > 0 ? top : 0;
        $clone.remove();
        $(this).find('.modal-content').css("margin-top", top);
    });
}
$('#modal-demo').on('show.bs.modal', centerModals);
$(window).on('resize', centerModals);

/**
 * 弹出层可拖动
 *
 */
$("#modal-demo").draggable({
    handle: ".modal-header",
    cursor: 'move',
    refreshPositions: false
});
