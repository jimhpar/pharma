<div class="modal fade text-left" id="status-Modal" tabindex="-1" role="dialog"
    aria-labelledby="myModalLabel1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <form action="" method="" id="statusForm">
           @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel1">Order Status Chnage</h5>
                <button type="button" class="close rounded-pill" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
               <div class="row">
                <input type="hidden" name="orderId" value="{{$order->id}}">
                <input type="hidden" name="currentStatus" value="{{$order->status}}">               

                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Order Status</label>
                    <select class="form-select" name="orderStatus">
                        <option value="draft" @if(isset($order) && $order->status == 'draft') selected @endif>Draft</option>
                        <option value="pending" @if(isset($order) && $order->status == 'pending') selected @endif>Pending</option>
                        <option value="confirmed" @if(isset($order) && $order->status == 'confirmed') selected @endif>Confirmed</option>
                        <option value="processing" @if(isset($order) && $order->status == 'processing') selected @endif>Processing</option>
                        <option value="packed" @if(isset($order) && $order->status == 'packed') selected @endif>Packed</option>
                        <option value="shipped" @if(isset($order) && $order->status == 'shipped') selected @endif>Shipped</option>
                        <option value="delivered" @if(isset($order) && $order->status == 'delivered') selected @endif>Delivered</option>
                        <option value="completed" @if(isset($order) && $order->status == 'completed') selected @endif>Completed</option>
                        <option value="returned" @if(isset($order) && $order->status == 'returned') selected @endif>Returned</option>
                        <option value="cancelled" @if(isset($order) && $order->status == 'cancelled') selected @endif>Cancelled</option>
                    </select>  
                    @error('orderStatus')
                    <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                        <strong>{{ $message }}</strong>
                    </div>
                    @enderror                  
                </div>                           
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
                <button type="button" class="btn btn-primary ms-1" data-bs-dismiss="modal" onclick="statusSave()">
                    <i class="bx bx-check d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Submit</span>
                </button>
            </div>
        </div>
        </form>
    </div>
</div>
<script>
    function statusSave() {
        let currentStatus = $('[name="currentStatus"]').val();
        let newStatus = $('[name="orderStatus"]').val();

        const allowedTransitions = {
            draft: ['pending', 'confirmed', 'cancelled'],
            pending: ['confirmed', 'processing', 'cancelled'],
            confirmed: ['processing', 'packed', 'cancelled'],
            processing: ['packed', 'shipped', 'cancelled'],
            packed: ['shipped', 'cancelled'],
            shipped: ['delivered', 'returned'],
            delivered: ['completed', 'returned'],
            completed: ['returned'],
            returned: [],
            cancelled: []
        };

        if (currentStatus !== newStatus && !allowedTransitions[currentStatus]?.includes(newStatus)) {
            toastr.warning(`You cannot move to '${newStatus}' from '${currentStatus}'.`);
            return;
        }

        $.ajax({
            url: "{{ route('orderStatus.store') }}",
            method: 'post',
            cache: false,
            processData: false,
            contentType: false,
            data: new FormData($("#statusForm")[0]),
            success: function (data) {
                let {message} =data
                toastr.success(`${message}`)               
                $('#status-Modal').modal('hide');
                $("#statusForm").trigger('reset')
                setTimeout(function () {
                    location.reload();
                }, 1500);
            },
            error: function (err) {
            if (err.status === 422) {
                if (err.responseJSON.errors) {                   
                    $("#statusForm").find("small").remove();
                    $("#statusForm").find('.is-invalid').removeClass('is-invalid');
                    $("#statusForm").find('.invalid-feedback').remove();

                    $.each(err.responseJSON.errors, function (i, error) {
                        var el = $('[name="' + i + '"]');
                        el.addClass('is-invalid');

                        var feedback = $('<div class="invalid-feedback"><strong>' + error[0] + '</strong></div>');
                        if (el.next('.invalid-feedback').length === 0) {
                            el.after(feedback);
                        }
                    });
                } else if (err.responseJSON.message) {                    
                    toastr.error(err.responseJSON.message);
                }
            } else {
                toastr.error("An unexpected error occurred.");
            }
        }
        });
    }
</script>
