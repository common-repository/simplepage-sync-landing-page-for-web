jQuery(document).ready(function ($) {
    let inputRmConnectInPage = '';
    $('.removeTemplate').click(function () {
        $('input[name*=\'simplepageSettings[templates]['+$(this).data('slug')+']\']')[0].remove();
        $(this).parents('.templateSimplePage').remove();
        inputRmConnectInPage = '<input type="hidden" name="simplepageSettings[rmConnectInPage][]" value="'+$(this).data('slug')+'">';
        $('#simplepageFormTable').append(inputRmConnectInPage);
    });
    $('#listLDP select').change(function () {
        let templates = $('#simplepageTemplates');
        let optionSelected = $(this).children(':selected').text() + ' - SimplePage.vn';
        templates.attr('name', 'simplepageSettings[templates][' + this.value + '.php]');
        templates.attr('value', optionSelected);

        let faviconUrl = $('#simplepageFavicon');
        faviconUrl.attr('value', $(this).children(':selected').data('fav'));
    });
    $('#getListLDP button').click(function (e) {
        e.preventDefault();
        let tokenSimplePage = $('#TokenSimplePage').val();
        if (tokenSimplePage !== '') {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'getListLandingPage',
                    simplepageNonce: simplepageGetLDP.simplepageGetLDP_nonce,
                    tokenSimplePage: tokenSimplePage,
                },
                dataType: 'json',
                beforeSend: function () {
                    $('.imgLoading').removeClass('hidden');
                },
                success: function (result) {
                    if (result.status === 'success') {
                        let htmlOption = '';
                        let dataFav = '';
                        $('#listLDP,#listPage,.simplepageButton').removeClass('hidden');
                        $('#getListLDP').addClass('hidden');

                        result.data.forEach(function (value) {
                            dataFav = value.favProject ? 'data-fav="' + value.favProject + '"' : '';
                            htmlOption += '<option value="' + value.slugProject + '" ' + dataFav + '>' + value.nameProject + '</option>';
                        });
                        $('#listLDP select').append(htmlOption);
                    } else if (result.status === 'error') {
                        alert(result.message);
                    } else {
                        console.log(result);
                    }
                    $('.imgLoading').addClass('hidden')
                },
                error: function (result) {
                    console.log(result);
                }
            });
        } else {
            alert('Chưa nhập Token Simple Page');
        }
    });
});
