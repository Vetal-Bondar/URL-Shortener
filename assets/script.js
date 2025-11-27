jQuery(document).ready(function($) {
    $('#url-shortener-form').on('submit', function(e) {
        e.preventDefault();

        var url = $('input[name="original_url"]').val();
        var resultDiv = $('#url-shortener-result');

        resultDiv.html('Обробка');

     $.ajax({
     type: 'POST',
     url: url_shortener_obj.ajax_url,
     data: {
            action: 'shorten_url',
            security: url_shortener_obj.nonce,
            url: url
            },
            success: function(response) {
                //вивід результату , успіх або помилка
                if (response.success) {
                    resultDiv.html('<div class="success">Готово: <a href="' + response.data + '" target="_blank">' + response.data + '</a></div>');
                } else {
                    //вивід помилки
                    resultDiv.html('<div class="error">' + response.data + '</div>');
                }
            },
        });
    });
});