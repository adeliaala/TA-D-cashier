                    <div class="form-group">
                        <label for="discount_percentage">Discount Percentage</label>
                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" value="{{ $sale->discount_percentage }}">
                    </div>
                    <div class="form-group">
                        <label for="discount_amount">Discount Amount</label>
                        <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0" value="{{ $sale->discount_amount }}" readonly>
                    </div>
                    <div class="form-group">
                        <label for="total_amount">Total Amount</label>
                        <input type="number" class="form-control" id="total_amount" name="total_amount" min="0" value="{{ $sale->total_amount }}" readonly> 