jQuery(document).ready(function($){

    $('.like-post').click(function(){

        $.ajax({
            url  : dypl.ajax_url,
            type  : 'POST',
            data  : {
                action : 'dypl_like',
                post_id:$(this).data('id'),
                like :! $(this).hasClass('post-liked'),
            },
            success:function(result){
                console.log(result);
            }
        });
    });
});