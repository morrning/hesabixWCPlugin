jQuery(function ($) {
    $('.progress').hide();
    'use strict';
//=====================================================================================================================
    $(function () {
        // AJAX - Export Products
        $('#ssbhesabix_export_products').submit(function () {
            // show processing status
            $('#ssbhesabix-export-product-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-export-product-submit').removeClass('button-primary');
            $('#ssbhesabix-export-product-submit').html('<i class="ofwc-spinner"></i> خروج محصولات...');
            $('#ssbhesabix-export-product-submit i.spinner').show();

            $('#exportProductsProgress').show();
            $('#exp  ortProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportProducts(1, 1, 1, 0);

            return false;
        });
    });
//=====================================================================================================================
    function exportProducts(batch, totalBatch, total, updateCount) {
        const data = {
            'action': 'adminExportProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportProducts(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#exportProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در استخراج محصولات');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Import Products
        $('#ssbhesabix_import_products').submit(function () {
            // show processing status
            $('#ssbhesabix-import-product-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-import-product-submit').removeClass('button-primary');
            $('#ssbhesabix-import-product-submit').html('<i class="ofwc-spinner"></i> در حال ورود کالاها از حسابیکس, لطفاً صبر کنید...');
            $('#ssbhesabix-import-product-submit i.spinner').show();

            $('#importProductsProgress').show();
            $('#importProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            importProducts(1, 1, 1, 0);

            return false;
        });
    });
//=====================================================================================================================
    function importProducts(batch, totalBatch, total, updateCount) {
        var data = {
            'action': 'adminImportProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#importProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    //alert('batch: ' + res.batch + ', totalBatch: ' + res.totalBatch + ', total: ' + res.total);
                    importProducts(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#importProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در وارد کردن محصولات');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Export Products opening quantity
        $('#ssbhesabix_export_products_opening_quantity').submit(function () {
            // show processing status
            $('#ssbhesabix-export-product-opening-quantity-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-export-product-opening-quantity-submit').removeClass('button-primary');
            $('#ssbhesabix-export-product-opening-quantity-submit').html('<i class="ofwc-spinner"></i> استخراج موجودی اول دوره...');
            $('#ssbhesabix-export-product-opening-quantity-submit i.spinner').show();

            $('#exportProductsOpeningQuantityProgress').show();
            $('#exportProductsOpeningQuantityProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportProductsOpeningQuantity(1, 1, 1);

            return false;
        });
    });

    function exportProductsOpeningQuantity(batch, totalBatch, total) {
        var data = {
            'action': 'adminExportProductsOpeningQuantity',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportProductsOpeningQuantityProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportProductsOpeningQuantity(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#exportProductsOpeningQuantityProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در استخراج موجودی اول دوره');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Export Customers
        $('#ssbhesabix_export_customers').submit(function () {
            // show processing status
            $('#ssbhesabix-export-customer-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-export-customer-submit').removeClass('button-primary');
            $('#ssbhesabix-export-customer-submit').html('<i class="ofwc-spinner"></i> خروجی مشتریان، لطفاً صبر کنید...');
            $('#ssbhesabix-export-customer-submit i.spinner').show();

            $('#exportCustomersProgress').show();
            $('#exportCustomersProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            exportCustomers(1, 1, 1, 0);

            return false;
        });
    });

    function exportCustomers(batch, totalBatch, total, updateCount) {
        const data = {
            'action': 'adminExportCustomers',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount
        };
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#exportCustomersProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    exportCustomers(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#exportCustomersProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در استخراج مشتریان');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Sync Changes
        $('#ssbhesabix_sync_changes').submit(function () {
            // show processing status
            $('#ssbhesabix-sync-changes-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-sync-changes-submit').removeClass('button-primary');
            $('#ssbhesabix-sync-changes-submit').html('<i class="ofwc-spinner"></i> همسان سازی تغییرات...');
            $('#ssbhesabix-sync-changes-submit i.spinner').show();

            var data = {
                'action': 'adminSyncChanges'
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('خطا در همگام سازی تغییرات');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });
    });
//=====================================================================================================================
    $(function () {
        // AJAX - Sync Products
        $('#ssbhesabix_sync_products').submit(function () {

            // show processing status
            $('#ssbhesabix-sync-products-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-sync-products-submit').removeClass('button-primary');
            $('#ssbhesabix-sync-products-submit').html('<i class="ofwc-spinner"></i> همسان سازی محصولات...');
            $('#ssbhesabix-sync-products-submit i.spinner').show();

            $('#syncProductsProgress').show();
            $('#syncProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            syncProducts(1, 1, 1);

            return false;
        });
    });
//=====================================================================================================================
    function syncProducts(batch, totalBatch, total) {
        const data = {
            'action': 'adminSyncProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        //$.post(URL, DATA, CALLBACK)
        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#syncProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    //alert('batch: ' + res.batch + ', totalBatch: ' + res.totalBatch + ', total: ' + res.total);
                    syncProducts(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#syncProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در همگام سازی محصولات');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Sync Orders
        $('#ssbhesabix_sync_orders').submit(function () {
            // show processing status
            $('#ssbhesabix-sync-orders-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-sync-orders-submit').removeClass('button-primary');
            $('#ssbhesabix-sync-orders-submit').html('<i class="ofwc-spinner"></i> همسان سازی سفارشات...');
            $('#ssbhesabix-sync-orders-submit i.spinner').show();

            $('#syncOrdersProgress').show();
            $('#syncOrdersProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            syncOrders(1, 1, 1, 0);

            return false;
        });
    });

    function syncOrders(batch, totalBatch, total, updateCount) {
        var date = $('#ssbhesabix_sync_order_date').val();
        var endDate = $('#ssbhesabix_sync_order_end_date').val();

        const data = {
            'action': 'adminSyncOrders',
            'date': date,
            'endDate': endDate,
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total,
            'updateCount': updateCount,
        };

        $.post(ajaxurl, data, function (response) {
            if (response !== 'failed') {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if(res.batch) $('#syncOrdersStatistics').html(`<div>پارت: ${res.batch} از ${res.totalBatch} - تعداد کل: ${res.total}</div>`);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#syncOrdersProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    syncOrders(res.batch + 1, res.totalBatch, res.total, res.updateCount);
                    return false;
                } else {
                    $('#syncOrdersProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در همگام سازی سفارشات');
                return false;
            }
        });
    }
//=====================================================================================================================
    $(function () {
        // AJAX - Sync Products
        $('#ssbhesabix_update_products').submit(function () {
            // show processing status
            $('#ssbhesabix-update-products-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-update-products-submit').removeClass('button-primary');
            $('#ssbhesabix-update-products-submit').html('<i class="ofwc-spinner"></i> بروزرسانی محصولات...');
            $('#ssbhesabix-update-products-submit i.spinner').show();

            $('#updateProductsProgress').show();
            $('#updateProductsProgressBar').css('width', 0 + '%').attr('aria-valuenow', 0);

            updateProducts(1, 1, 1);

            return false;
        });
    });
//=====================================================================================================================
    function updateProducts(batch, totalBatch, total) {
        var data = {
            'action': 'adminUpdateProducts',
            'batch': batch,
            'totalBatch': totalBatch,
            'total': total
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                res.batch = parseInt(res.batch);
                if (res.batch < res.totalBatch) {
                    let progress = (res.batch * 100) / res.totalBatch;
                    progress = Math.round(progress);
                    $('#updateProductsProgressBar').css('width', progress + '%').attr('aria-valuenow', progress);
                    updateProducts(res.batch + 1, res.totalBatch, res.total);
                    return false;
                } else {
                    $('#updateProductsProgressBar').css('width', 100 + '%').attr('aria-valuenow', 100);
                    setTimeout(() => {
                        top.location.replace(res.redirectUrl);
                    }, 1000);
                    return false;
                }
            } else {
                alert('خطا در بروزرسانی محصولات');
                return false;
            }
        });
    }

//=====================================================================================================================

    // $(function () {
    //     // AJAX - Sync Products with ID filter
    //     $('#ssbhesabix_update_products_with_filter').submit(function (e) {
    //
    //         // Show processing status
    //         var submitButton = $('#ssbhesabix-update-products-with-filter-submit');
    //         submitButton.removeClass('button-primary');
    //         submitButton.html('<i class="ofwc-spinner"></i> بروزرسانی محصولات...');
    //     });
    // });



    $(function () {
        // AJAX - Sync Products
        $('#ssbhesabix_update_products_with_filter').submit(function () {
            let submitButton = $('#ssbhesabix-update-products-with-filter-submit');
            let offset = document.getElementById("ssbhesabix-update-products-offset").value;
            let rpp = document.getElementById("ssbhesabix-update-products-rpp").value;
            submitButton.removeClass('button-primary');
            submitButton.html('<i class="ofwc-spinner"></i> بروزرسانی محصولات لطفا صبر کنید...');
            $('#ssbhesabix-update-products-with-filter-submit').attr('disabled', 'disabled');

            updateProductsWithFilter(offset, rpp);

            return false;
        });
    });
//=====================================================================================================================
    function updateProductsWithFilter(offset, rpp) {
        var data = {
            'action': 'adminUpdateProductsWithFilter',
            'offset': offset,
            'rpp': rpp,
        };
        if(offset && rpp) {
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    if(!res.error) {
                        top.location.replace(res.redirectUrl);
                    }
                } else {
                    alert('خطا در بروزرسانی محصولات');
                    return false;
                }
            });
        } else {
            alert('فیلد ها را به درستی وارد نمایید');
            submitButton.addClass('button-primary');
            submitButton.html('بروزرسانی محصولات در حسابیکس بر اساس فروشگاه در بازه ID مشخص شده');
            $('#ssbhesabix-update-products-with-filter-submit').removeAttr('disabled');
            return false;
        }
    }

//=====================================================================================================================
    $(function () {
        // AJAX - Clean log
        $('#ssbhesabix_clean_log').submit(function (e) {
            // show processing status
            $('#ssbhesabix-log-clean-submit').attr('disabled', 'disabled');
            $('#ssbhesabix-log-clean-submit').removeClass('button-primary');
            $('#ssbhesabix-log-clean-submit').html('<i class="ofwc-spinner"></i> پاک کردن فایل لاگ، لطفاً صبر کنید...');
            $('#ssbhesabix-log-clean-submit i.spinner').show();

            var data = {
                'action': 'adminCleanLogFile'
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('خطا در پاکسازی فایل لاگ');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });
    });
//=====================================================================================================================
    $(function () {
        // AJAX - Sync Products Manually
        $('#ssbhesabix_sync_products_manually').submit(function () {
            // show processing status
            $('#ssbhesabix_sync_products_manually-submit').attr('disabled', 'disabled');
            $('#ssbhesabix_sync_products_manually-submit').removeClass('button-primary');
            $('#ssbhesabix_sync_products_manually-submit').html('<i class="ofwc-spinner"></i> ذخیره کردن اطلاعات...');
            $('#ssbhesabix_sync_products_manually i.spinner').show();

            const inputArray = [];
            const inputs = $('.code-input');
            console.log(inputs);
            for (var n = 0; n < inputs.length; n++) {
                var i = inputs[n];
                console.log(i);
                const obj = {
                    id: $(i).attr('id'),
                    hesabix_id: $(i).val(),
                    parent_id: $(i).attr('data-parent-id')
                }
                inputArray.push(obj);
            }

            const page = $('#pageNumber').val();
            const rpp = $('#goToPage').attr('data-rpp');

            var data = {
                'action': 'adminSyncProductsManually',
                'data': JSON.stringify(inputArray),
                'page': page,
                'rpp': rpp
            };

            // post it
            $.post(ajaxurl, data, function (response) {
                if ('failed' !== response) {
                    var redirectUrl = response;

                    /** Debug **/
                    // console.log(redirectUrl);
                    // return false;

                    top.location.replace(redirectUrl);
                    return false;
                } else {
                    alert('خطا در ذخیره اطلاعات');
                    return false;
                }
            });
            /*End Post*/
            return false;
        });

        $("#goToPage").click(function () {
            const page = $('#pageNumber').val();
            const rpp = $('#goToPage').attr('data-rpp');
            window.location.href = "?page=hesabix-sync-products-manually&p=" + page + "&rpp=" + rpp;
        });

        $("#show-tips-btn").click(function () {
            $('#tips-alert').removeClass('d-none');
            $('#tips-alert').addClass('d-block');
        });

        $("#hide-tips-btn").click(function () {
            $('#tips-alert').removeClass('d-block');
            $('#tips-alert').addClass('d-none');
        });
    });

    $(".btn-submit-invoice").on( "click", function() {
        var orderId = $(this).attr("data-order-id");

        var btnEl = $('.btn-submit-invoice[data-order-id=' + orderId + ']');

        btnEl.attr('aria-disabled', true);
        btnEl.addClass('disabled');
        btnEl.html('ثبت فاکتور...');
        //btnEl.show();

        submitInvoice(orderId);
    });
//=====================================================================================================================
    function submitInvoice(orderId) {
        var data = {
            'action': 'adminSubmitInvoice',
            'orderId': orderId
        };
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response) {
                const res = JSON.parse(response);
                // refresh page
                location.reload();
            } else {
                alert('خطا در ثبت فاکتور');
                return false;
            }
        });
    }

    // change business warning
    var oldApiKey = '';
    $("#changeBusinessWarning").hide();

    $("#ssbhesabix_account_api").focusin( function () {
        oldApiKey = $("#ssbhesabix_account_api" ).val();
    });
    $("#ssbhesabix_account_api").focusout( function () {
        var newApiKey = $("#ssbhesabix_account_api" ).val();
        if(oldApiKey != '' && oldApiKey != newApiKey) {
            $("#changeBusinessWarning").show();
        }
    });
//=====================================================================================================================
    $(function () {
        // AJAX - clear all plugin data
        $('#hesabix-clear-plugin-data').click(function () {
            if (confirm('هشدار: با انجام این عملیات کلیه اطلاعات افزونه شامل روابط بین کالاها، مشتریان و فاکتور ها و همینطور تنظیمات افزونه حذف می گردد.' +
                'آیا از انجام این عملیات مطمئن هستید؟')) {
                $('#hesabix-clear-plugin-data').addClass('disabled');
                $('#hesabix-clear-plugin-data').html('حذف دیتای افزونه...');
                var data = {
                    'action': 'adminClearPluginData'
                };
                $.post(ajaxurl, data, function (response) {
                    $('#hesabix-clear-plugin-data').removeClass('disabled');
                    $('#hesabix-clear-plugin-data').html('حذف دیتای افزونه');
                    if ('failed' !== response) {
                        alert('دیتای افزونه با موفقیت حذف شد.');
                        return false;
                    } else {
                        alert('خطا در هنگام حذف دیتای افزونه.');
                        return false;
                    }
                });
            } else {
                // Do nothing!
            }
            return false;
        });

        $('#hesabix-install-plugin-data').click(function () {
            if (confirm('با انجام این عملیات جدول افزونه در دیتابیس وردپرس ایجاد' +
                ' و تنظیمات پیش فرض افزونه تنظیم می گردد.' +
                ' آیا از انجام این عملیات مطمئن هستید؟')) {
                $('#hesabix-install-plugin-data').addClass('disabled');
                $('#hesabix-install-plugin-data').html('نصب دیتای افزونه...');
                var data = {
                    'action': 'adminInstallPluginData'
                };
                $.post(ajaxurl, data, function (response) {
                    $('#hesabix-install-plugin-data').removeClass('disabled');
                    $('#hesabix-install-plugin-data').html('نصب دیتای افزونه');
                    if ('failed' !== response) {
                        alert('دیتای افزونه با موفقیت نصب شد.');
                        return false;
                    } else {
                        alert('خطا در هنگام نصب دیتای افزونه.');
                        return false;
                    }
                });
            } else {
                // Do nothing!
            }
            return false;
        });
    });
//=====================================================================================================================
    $(function () {
        //SAVE
        $(".hesabix-item-save").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            const attributeId = $(this).data('id');
            const code = $("#hesabix-item-" + attributeId).val();
            var data = {
                'action': 'adminChangeProductCode',
                'productId': productId,
                'attributeId': attributeId,
                'code': code,
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    alert(res.error ? res.message : 'کد کالای متصل با موفقیت تغییر کرد.');
                    if(productId === attributeId)
                        $("#ssbhesabix_hesabix_item_code_0").val(code);
                    else
                        $("#ssbhesabix_hesabix_item_code_" + attributeId).val(code);
                    return false;
                } else {
                    alert('خطا در هنگام تغییر کد کالای متصل.');
                    return false;
                }
            });
        });
        //DELETE LINK
        $(".hesabix-item-delete-link").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            const attributeId = $(this).data('id');
            var data = {
                'action': 'adminDeleteProductLink',
                'productId': productId,
                'attributeId': attributeId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    $("#hesabix-item-" + attributeId).val('');
                    if(productId === attributeId)
                        $("#ssbhesabix_hesabix_item_code_0").val('');
                    else
                        $("#ssbhesabix_hesabix_item_code_" + attributeId).val('');
                    setTimeout(function (){
                        alert(res.error ? res.message : 'ارتباط محصول با موفقیت حذف شد');
                    }, 100);
                    return false;
                } else {
                    alert('خطا در هنگام حذف ارتباط');
                    return false;
                }
            });
        });
        //UPDATE
        $(".hesabix-item-update").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            const attributeId = $(this).data('id');
            var data = {
                'action': 'adminUpdateProduct',
                'productId': productId,
                'attributeId': attributeId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    if(res.newPrice != null)
                        $("#hesabix-item-price-" + attributeId).text(res.newPrice);
                    if(res.newQuantity != null)
                        $("#hesabix-item-quantity-" + attributeId).text(res.newQuantity);
                    if(res.error)
                        alert(res.message);
                    return false;
                } else {
                    alert('خطا در هنگام بروزرسانی محصول');
                    return false;
                }
            });
        });
        //SAVE ALL
        $("#hesabix-item-save-all").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            const itemsCode = $(".hesabix-item-code");
            const itemsData = [];
            for (let i = 0; i < itemsCode.length; i++) {
                const item = itemsCode[i];
                const attributeId = $(item).data('id');
                const code = $(item).val();
                itemsData.push({attributeId: attributeId, code: code});
            }

            var data = {
                'action': 'adminChangeProductsCode',
                'productId': productId,
                'itemsData': itemsData
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    alert(res.error ? res.message : 'کد کالاهای متصل با موفقیت تغییر کرد.');
                    location.reload();
                    return false;
                } else {
                    alert('خطا در هنگام تغییر کد کالاهای متصل');
                    return false;
                }
            });
        });
        //DELETE
        $("#hesabix-item-delete-link-all").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            var data = {
                'action': 'adminDeleteProductsLink',
                'productId': productId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    const itemsCode = $(".hesabix-item-code");
                    for (let i = 0; i < itemsCode.length; i++) {
                        const item = itemsCode[i];
                        $(item).val('');
                    }
                    $('[id^="ssbhesabix_hesabix_item_code_"]').val('');
                    setTimeout(function (){
                        alert(res.error ? res.message : 'ارتباط محصولات با موفقیت حذف شد.');
                    }, 100);
                    return false;
                } else {
                    alert('خطا در هنگام حذف ارتباط');
                    return false;
                }
            });
        });
        //UPDATE ALL
        $("#hesabix-item-update-all").on('click', function (){
            const productId = $("#panel_product_data_hesabix").data('product-id');
            var data = {
                'action': 'adminUpdateProductAndVariations',
                'productId': productId
            };
            $(this).prop('disabled', true);
            const _this = this;
            $.post(ajaxurl, data, function (response) {
                $(_this).prop('disabled', false);
                if ('failed' !== response) {
                    const res = JSON.parse(response);
                    if(res.error)
                    {
                        alert(res.message);
                        return false;
                    }
                    for (let i = 0; i < res.newData.length; i++) {
                        if(res.newData[i].newPrice != null)
                            $("#hesabix-item-price-" + res.newData[i].attributeId).text(res.newData[i].newPrice);
                        if(res.newData[i].newQuantity != null)
                            $("#hesabix-item-quantity-" + res.newData[i].attributeId).text(res.newData[i].newQuantity);
                    }
                    return false;
                } else {
                    alert('خطا در هنگام بروزرسانی محصول');
                    return false;
                }
            });
        });
    });
//=====================================================================================================================
    $(function (){
        let radio           = $('input:radio[name="addFieldsRadio"]');
        let radioChecked    = $('input:radio[name="addFieldsRadio"]:checked');
        let textInput       = $('.contact_text_input');

        if(radioChecked.val() === '2'){
            textInput.prop( "disabled", false );
        }else {
            textInput.prop( "disabled", true );
        }
        $(radio).on('click',function (){
            if($(this).val() === '2'){
                textInput.prop( "disabled", false );
            }else {
                textInput.prop( "disabled", true );
            }
        });

    });
});
//=====================================================================================================================
function hesabixTutorialJumpTo(time) {
    let vidEl = document.getElementById('hesabix-tutorial-video');
    vidEl.play();
    vidEl.pause();
    vidEl.currentTime = time;
    vidEl.play();
}
