@extends($activeTemplate . 'layouts.' . $layout)

@section('content')
    <div class="card-area pt-80 pb-80">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="card custom--card">
                        <div class="card-header card-header-bg d-flex flex-wrap justify-content-between align-items-center">
                            <h5 class="text-white mt-0">
                                @php echo $myTicket->statusBadge; @endphp
                                [@lang('Ticket')#{{ $myTicket->ticket }}] {{ $myTicket->subject }}
                            </h5>
                            @if ($myTicket->status != Status::TICKET_CLOSE && $myTicket->user)
                                <button class="btn btn-danger close-button btn--sm confirmationBtn" type="button" data-question="@lang('Are you sure to close this ticket?')" data-action="{{ route('ticket.close', $myTicket->id) }}"><i class="fas fa-lg fa-times-circle"></i>
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            <form method="post" class="disableSubmission" action="{{ route('ticket.reply', $myTicket->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row justify-content-between">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea name="message" class="form-control form--control" rows="4" required>{{ old('message') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-9">
                                        <button type="button" class="btn btn--base btn--sm addAttachment my-2"> <i class="las la-plus"></i> @lang('Add Attachment') </button>
                                        <p class="mb-2"><span class="text--info">@lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</span></p>
                                        <div class="row fileUploadsContainer">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn--base w-100 my-2" type="submit"><i class="la la-fw la-lg la-reply"></i> @lang('Reply')</button>
                                    </div>
                                </div>
                            </form>
                            @forelse($messages as $message)
                                @if ($message->admin_id == 0)
                                    <div class="row border--primary border-radius-3 my-3 mx-1 border py-3">
                                        <div class="col-md-3 border-right text-right">
                                            <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                        </div>
                                        <div class="col-md-9">
                                            <p class="font-weight-bold my-3">
                                                @lang('Posted on') {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                            <p>{{ $message->message }}</p>
                                            @if ($message->attachments()->count() > 0)
                                                <div class="mt-2">
                                                    @foreach ($message->attachments as $k => $image)
                                                        <a class="me-3 base--color" href="{{ route('ticket.download', encrypt($image->id)) }}"><i
                                                               class="la la-file"></i> @lang('Attachment') {{ ++$k }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="row border--warning border-radius-3 my-3 mx-1 border py-3">
                                        <div class="col-md-3 border-right text-right">
                                            <h5 class="my-3">{{ $message->admin->name }}</h5>
                                            <p class="lead">@lang('Staff')</p>
                                        </div>
                                        <div class="col-md-9">
                                            <p class="font-weight-bold my-3">
                                                @lang('Posted on') {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                            <p>{{ $message->message }}</p>
                                            @if ($message->attachments()->count() > 0)
                                                <div class="mt-2">
                                                    @foreach ($message->attachments as $k => $image)
                                                        <a class="me-3 base--color" href="{{ route('ticket.download', encrypt($image->id)) }}"><i
                                                               class="la la-file"></i> @lang('Attachment') {{ ++$k }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="empty-message text-center">
                                    <img src="{{ asset('assets/images/empty_list.png') }}" alt="empty">
                                    <h5 class="text-muted">@lang('No replies found here!')</h5>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-confirmation-modal closeBtn="btn btn--danger btn--sm" baseBtn="btn btn--base btn--sm" />
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            var fileAdded = 0;
            $('.addAttachment').on('click', function() {
                fileAdded++;
                if (fileAdded == 5) {
                    $(this).attr('disabled', true)
                }
                $(".fileUploadsContainer").append(`
                    <div class="col-lg-4 col-md-12 removeFileInput">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="file" name="attachments[]" class="form-control form--control custom--file-upload" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx" required>
                                <button type="button" class="input-group-text removeFile bg--danger border--danger"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                `)
            });
            $(document).on('click', '.removeFile', function() {
                $('.addAttachment').removeAttr('disabled', true)
                fileAdded--;
                $(this).closest('.removeFileInput').remove();
            });
        })(jQuery);
    </script>
@endpush
