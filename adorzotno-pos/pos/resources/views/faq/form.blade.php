<div class="row">
    <div class="col-12">
        <div class="form-group">
            <label for="question">Question <span class="text-danger">*</span></label>
            <textarea id="question" class="form-control @error('question') is-invalid @enderror" name="question" rows="3">{{ old('question', $faq->question ?? '') }}</textarea>
            @error('question')
                <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="answer">Answer <span class="text-danger">*</span></label>
            <textarea id="answer" class="form-control @error('answer') is-invalid @enderror" name="answer" rows="6">{{ old('answer', $faq->answer ?? '') }}</textarea>
            @error('answer')
                <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('faq.show') }}" class="btn btn-light-secondary me-1 mb-1">Cancel</a>
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
