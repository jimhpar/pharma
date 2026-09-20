<div class="modal fade text-left" id="payment-Modal" tabindex="-1" role="dialog"
    aria-labelledby="myModalLabel1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <form action="" method="" id="paymentForm">
           @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel1">Payment</h5>
                <button type="button" class="close rounded-pill" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
               <div class="row">
                <input type="hidden" name="orderId" value="{{$order->id}}">               
                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Order Total</label>
                     <input type="number" readonly id="first-name-horizontal" class="form-control" name="orderTotal" min="1" value="{{$order->grand_total}}"
                        placeholder="Total Amount Paid">
                    @error('orderTotal')
                    <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                        <strong>{{ $message }}</strong>
                    </div>
                    @enderror
                </div>
                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Total Paid</label>
                     <input type="number" readonly id="first-name-horizontal" class="form-control" name="totalPaid" min="1" value="{{ $order->payments->sum('amount') ?? 0 }}"
                        placeholder="Total Amount Paid">
                    @error('totalPaid')
                    <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                        <strong>{{ $message }}</strong>
                    </div>
                    @enderror
                </div>
                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Amount</label>
                     <input type="number" id="first-name-horizontal" class="form-control" name="amount" min="1"
                        placeholder="Enter Amount">
                    @error('amount')
                    <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                        <strong>{{ $message }}</strong>
                    </div>
                    @enderror
                </div>
                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Payment Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Mobile Banking">Mobile Banking</option>
                        <option value="Bank">Bank</option>
                        <option value="Wallet">Wallet</option>
                        <option value="Cheque">Cheque</option>
                    </select>  
                    @error('payment_method')
                    <div class="invalid-feedback">
                    <i class="bx bx-radio-circle"></i>
                        <strong>{{ $message }}</strong>
                    </div>
                    @enderror                  
                </div>
                
                <div class="col-md-12 mb-2">
                    <label>Payment Status</label>
                    <div class="form-control bg-light">
                        Status updates automatically based on the amount you receive.
                    </div>
                </div>

                <div class="col-md-12 mb-2">
                    <label for="first-name-horizontal">Note</label>
                     <textarea type="text" rows="3" id="first-name-horizontal" class="form-control" name="note"
                        placeholder="Enter Note"></textarea>
                    @error('note')
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
                <button type="button" class="btn btn-primary ms-1" onclick="paymentSave()">
                    <i class="bx bx-check d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Accept</span>
                </button>
            </div>
        </div>
        </form>
    </div>
</div>
<script>
    function paymentSave() {
        let orderTotal = parseFloat($('[name="orderTotal"]').val()) || 0;
        let totalPaid = parseFloat($('[name="totalPaid"]').val()) || 0;
        let amount = parseFloat($('[name="amount"]').val()) || 0;
        let combinedPaid = totalPaid + amount;

        if (combinedPaid > orderTotal) {
            toastr.warning("Total payment (already paid + new amount) cannot exceed order total.");
            return;
        }

        $.ajax({
            url: "{{ route('transaction.savePayment') }}",
            method: 'post',
            cache: false,
            processData: false,
            contentType: false,
            data: new FormData($("#paymentForm")[0]),
            success: function (data) {
                let {message} =data
                toastr.success(`${message}`)               
                $('#payment-Modal').modal('hide');
                $("#paymentForm").trigger('reset')
                setTimeout(function () {
                    location.reload();
                }, 1500);
            },
            error: function (err) {
            if (err.status === 422) {
                if (err.responseJSON.errors) {                   
                    $("#paymentForm").find("small").remove();
                    $("#paymentForm").find('.is-invalid').removeClass('is-invalid');
                    $("#paymentForm").find('.invalid-feedback').remove();

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
