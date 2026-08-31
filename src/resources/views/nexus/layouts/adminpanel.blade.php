<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{asset('packages/jquery-colorbox/example1/colorbox.css')}}">

    <!-- bootstrap-datepicker css -->
    <link rel="stylesheet" href="{{asset('nexus/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css')}}"
        type="text/css">

    <!-- DataTables -->
    <link rel="stylesheet" href="{{asset('nexus/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')}}"
        type="text/css">

    <!-- Responsive datatable examples -->
    <link rel="stylesheet"
        href="{{asset('nexus/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')}}" type="text/css">

    <!-- Nexus theme font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Css -->
    <link rel="stylesheet" href="{{asset('nexus/css/bootstrap.min.css')}}" type="text/css" id="bootstrap-style">
    <!-- Icons Css -->
    <link rel="stylesheet" href="{{asset('nexus/css/icons.min.css')}}" type="text/css">
    <!-- App Css (layout mechanics: sidebar/topbar positioning, grid, responsive) -->
    <link id="app-style" rel="stylesheet" href="{{asset('nexus/css/app.min.css')}}" type="text/css">
    <!-- Nexus theme Css: overrides app.min.css tokens (colors, radius, shadows, typography) -->
    <link rel="stylesheet" href="{{asset('nexus/css/nexus-theme.css')}}" type="text/css">
    <!-- Magnific Popup Css -->
    <link rel="stylesheet" href="{{asset('nexus/libs/magnific-popup/magnific-popup.css')}}" type="text/css">
    <!-- App js -->
    <script src="{{asset('packages/jquery/jquery-3.7.1.min.js')}}"></script>
    <!-- Magnific Popup JS -->
    <script src="{{asset('nexus/libs/magnific-popup/jquery.magnific-popup.min.js')}}"></script>
    <script src="{{asset('nexus/js/plugin.js')}}"></script>

    @yield('head')
    @yield('css')
    @stack('css')
    <!-- Choices.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/styles/choices.min.css" />
    <style>
        .image-popup {
            cursor: zoom-in !important;
        }
        .mfp-bg {
            z-index: 10501 !important;
        }
        .mfp-wrap {
            z-index: 10502 !important;
        }
    </style>
    @livewireStyles
</head>

{{--@php $activeLocale = \Brilliant\Brilara\Modules\Language\Registry\LanguageRegistry::get('is_active'); @endphp--}}
{{--

<body @if(isset($activeLocale) && $activeLocale->is_rtl) dir="rtl" class="body-rtl" @else dir="ltr" @endif >--}}

    <body>

        @include('nexus::' . config('nexus.template') . '.components.impersonation_banner')

        <!-- 0 Wrapper -->
        <div id="layout-wrapper">

            @include('nexus::' . config('nexus.template') . '.layouts.header')

            @include('nexus::' . config('nexus.template') . '.layouts.menu')

            @include('nexus::partials.module-dependency-warning')

            @include('nexus::' . config('nexus.template') . '.layouts.content')

            @if(session('alert_message') && config('nexus.toast.enabled'))
                @include('nexus::'. config('nexus.template'). '.components.toast', ['message' => session('alert_message'), 'type' => session('alert_type')])
            @endif

        </div>

        <!-- Modal -->
        <div class="modal fade" id="newCustomerModal" tabindex="-1" aria-labelledby="newCustomerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newCustomerModalLabel">Add Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form autocomplete="off" class="needs-validation createCustomer-form" id="createCustomer-form"
                            novalidate>
                            <div class="row">
                                <div class="col-lg-12">
                                    <input type="hidden" class="form-control" id="userid-input">
                                    <div class="mb-3">
                                        <label for="username-input" class="form-label">Customer Name</label>
                                        <input type="text" id="username-input" class="form-control"
                                            placeholder="Enter name" required />
                                        <div class="invalid-feedback">Please enter a name.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email-input" class="form-label">Email</label>
                                        <input type="email" id="email-input" class="form-control"
                                            placeholder="Enter email" required />
                                        <div class="invalid-feedback">Please enter email.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone-input" class="form-label">Phone</label>
                                        <input type="text" id="phone-input" class="form-control"
                                            placeholder="Enter Phone" required />
                                        <div class="invalid-feedback">Please enter amount.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="joindate-input" class="form-label">Join Date</label>
                                        <input type="text" id="joindate-input" class="form-control"
                                            placeholder="Select join date" data-date-format="dd M, yyyy"
                                            data-provide="datepicker" data-date-autoclose="true" required />
                                        <div class="invalid-feedback">Please select a join date.</div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="text-end">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" id="addCustomer-btn" class="btn btn-success">Add
                                            Customer</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- end modal body -->
                </div>
                <!-- end modal-content -->
            </div>
            <!-- end modal-dialog -->
        </div>
        <!-- end newCustomerModal -->


        <!-- Modal -->
        <div class="modal fade" id="removeItemModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body px-4 py-5 text-center">
                        <button type="button" class="btn-close position-absolute end-0 top-0 m-3"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="avatar-sm mb-4 mx-auto">
                            <div class="avatar-title bg-primary text-primary bg-opacity-10 font-size-20 rounded-3">
                                <i class="mdi mdi-trash-can-outline"></i>
                            </div>
                        </div>
                        <p class="text-muted font-size-16 mb-4">Are you Sure You want to Remove this User ?</p>

                        <div class="hstack gap-2 justify-content-center mb-0">
                            <button type="button" class="btn btn-danger" id="remove-item">Remove Now</button>
                            <button type="button" class="btn btn-secondary" id="close-removeCustomerModal"
                                data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end removeItemModal -->

        <!-- Right Sidebar -->
        <div class="right-bar">
            <div data-simplebar class="h-100">
                <div class="rightbar-title d-flex align-items-center px-3 py-4">

                    <h5 class="m-0 me-2">Settings</h5>

                    <a href="javascript:void(0);" class="right-bar-toggle ms-auto">
                        <i class="mdi mdi-close noti-icon"></i>
                    </a>
                </div>

                <!-- Settings -->
                <hr class="mt-0" />
                <h6 class="text-center mb-0">Choose Layouts</h6>

                <div class="p-4">
                    <div class="mb-2">
                        <img src="{{ asset('nexus/images/layouts/layout-1.jpg') }}" class="img-thumbnail"
                            alt="layout images">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input theme-choice" type="checkbox" id="light-mode-switch" checked>
                        <label class="form-check-label" for="light-mode-switch">Light Mode</label>
                    </div>

                    <div class="mb-2">
                        <img src="{{ asset('nexus/images/layouts/layout-2.jpg') }}" class="img-thumbnail"
                            alt="layout images">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input theme-choice" type="checkbox" id="dark-mode-switch">
                        <label class="form-check-label" for="dark-mode-switch">Dark Mode</label>
                    </div>

                    <div class="mb-2">
                        <img src="{{ asset('nexus/images/layouts/layout-3.jpg') }}" class="img-thumbnail"
                            alt="layout images">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input theme-choice" type="checkbox" id="rtl-mode-switch">
                        <label class="form-check-label" for="rtl-mode-switch">RTL Mode</label>
                    </div>

                    <div class="mb-2">
                        <img src="{{ asset('nexus/images/layouts/layout-4.jpg') }}" class="img-thumbnail"
                            alt="layout images">
                    </div>
                    <div class="form-check form-switch mb-5">
                        <input class="form-check-input theme-choice" type="checkbox" id="dark-rtl-mode-switch">
                        <label class="form-check-label" for="dark-rtl-mode-switch">Dark RTL Mode</label>
                    </div>


                </div>

            </div> <!-- end slimscroll-menu-->
        </div>
        <!-- /Right-bar -->

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>
        <!-- / Wrapper -->


        <!--   Core JS Files   -->
        {{-- bootstrap.bundle.min.js already includes everything in bootstrap.min.js plus Popper —
             loading both parsed/executed the plain build for nothing. --}}
        <script src="{{asset('nexus/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        {{--TODO ці 4 залежності мають або опубліковуватися. або ставитися разом з пакетом--}}
        <script src="{{asset('packages/jquery-colorbox/jquery.colorbox.js')}}"></script>
        <script src="{{asset('packages/barryvdh/elfinder/js/standalonepopup.js')}}"></script>
        <script src="{{asset('packages/ckeditor/ckeditor.js')}}"></script>
        <script src="{{asset('packages/ckeditor/adapters/jquery.js')}}"></script>


        <script src="{{asset('nexus/libs/metismenu/metisMenu.min.js')}}"></script>
        <script src="{{asset('nexus/libs/simplebar/simplebar.min.js')}}"></script>
        <script src="{{asset('nexus/libs/node-waves/waves.min.js')}}"></script>

        <script src="{{asset('nexus/js/app.js')}}"></script>
        <script src="{{asset('adminlte/plugins/dropzone/dropzone.js')}}"></script>

        {{--<form method="POST" id="logoutForm" action="{{ route('logout') }}">--}}
            {{-- @csrf--}}
            {{--</form>--}}
        <script>
            jQuery(document).ready(function () {
                jQuery('.logout_action').click(logoutForm);

                function logoutForm() {
                    document.forms["logoutForm"].submit();
                }

                // Global Magnific Popup initialization
                if (typeof jQuery.fn.magnificPopup === 'function') {
                    jQuery('body').magnificPopup({
                        delegate: '.image-popup',
                        type: 'image',
                        closeOnContentClick: true,
                        mainClass: 'mfp-img-mobile',
                        image: {
                            verticalFit: true
                        }
                    });
                }
            });
        </script>

        @if(isset($tableData) && isset($module->config->table->actionGroup) && !empty($module->config->table->actionGroup))
            <script>
                jQuery(document).ready(function () {
                    if (jQuery('#offcanvasSelected').length) {

                        let bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasSelected'));

                        jQuery('#check_all').change(function () {
                            let formGroup = jQuery(this).closest('.form-check-group');
                            if (jQuery(this).is(':checked')) {
                                formGroup.find('.form-check-input-single').prop('checked', true);
                                bsOffcanvas.show();
                            } else {
                                formGroup.find('.form-check-input-single').prop('checked', false);
                                bsOffcanvas.hide();
                            }
                            setItemsSelectedInfo();
                        });

                        jQuery('.form-check-input-single').change(function () {
                            let formGroup = jQuery(this).closest('.form-check-group');
                            let allChecked = formGroup.find('.form-check-input-single').length === formGroup.find('.form-check-input-single:checked').length;
                            jQuery('#check_all').prop('checked', allChecked);

                            if (formGroup.find('.form-check-input-single:checked').length > 0) {
                                bsOffcanvas.show();
                            } else {
                                bsOffcanvas.hide();
                            }
                            setItemsSelectedInfo();
                        });

                        function setItemsSelectedInfo() {
                            let ids = [];
                            jQuery('.form-check-input-single:checked').each(function () {
                                ids.push(jQuery(this).attr('data-id'));
                            });

                            // Очищаємо та наповнюємо всі форми групових дій
                            jQuery('.action-group-form').each(function () {
                                let wrapper = jQuery(this).find('.items_selected_wrapper');
                                wrapper.empty();
                                ids.forEach(function (id) {
                                    wrapper.append('<input type="hidden" name="items[]" value="' + id + '">');
                                });
                            });
                        }
                    }
                });
            </script>
        @endif

        @if(session('alert_message') && config('nexus.toast.enabled'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var toastEl = document.getElementById('liveToast');
                    var toast = new bootstrap.Toast(toastEl);
                    toast.show();
                });
            </script>
        @endif

        @yield('js')
        @stack('js')

        <script src="https://cdn.jsdelivr.net/npm/choices.js@11.2.4/public/assets/scripts/choices.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var choicesExamples = document.querySelectorAll("[data-choices]");
                choicesExamples.forEach(function (item) {
                    var choiceData = {};
                    var isChoicesVal = item.attributes;
                    if (isChoicesVal["data-choices-groups"]) {
                        choiceData.placeholderValue = "This is a placeholder set in the config";
                    }
                    if (isChoicesVal["data-choices-search-false"]) {
                        choiceData.searchEnabled = false;
                    }
                    if (isChoicesVal["data-choices-search-true"]) {
                        choiceData.searchEnabled = true;
                    }
                    if (isChoicesVal["data-choices-removeItem"]) {
                        choiceData.removeItemButton = true;
                    }
                    if (isChoicesVal["data-choices-sorting-false"]) {
                        choiceData.shouldSort = false;
                    }
                    if (isChoicesVal["data-choices-sorting-true"]) {
                        choiceData.shouldSort = true;
                    }
                    if (isChoicesVal["data-choices-multiple-remove"]) {
                        choiceData.removeItemButton = true;
                    }
                    if (isChoicesVal["data-choices-limit"]) {
                        choiceData.maxItemCount = isChoicesVal["data-choices-limit"].value.toString();
                    }
                    if (isChoicesVal["data-choices-editItem-true"]) {
                        choiceData.maxItemCount = true;
                    }
                    if (isChoicesVal["data-choices-editItem-false"]) {
                        choiceData.maxItemCount = false;
                    }
                    if (isChoicesVal["data-choices-text-unique-true"]) {
                        choiceData.duplicateItemsAllowed = false;
                        choiceData.paste = false;
                    }
                    if (isChoicesVal["data-choices-text-disabled-true"]) {
                        choiceData.addItems = false;
                    }
                    isChoicesVal["data-choices-text-disabled-true"] ? new Choices(item, choiceData).disable() : new Choices(item, choiceData);
                });
            });
        </script>

        @livewireScripts
    </body>

</html>
