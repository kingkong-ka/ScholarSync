@extends('layouts.app')

@section('content')
<h1>Edit Student Violation Manual</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('educator.manual.update') }}" method="POST">
    @csrf

    @foreach ($categories as $category)
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:20px;">
            <input type="hidden" name="categories[{{ $loop->index }}][id]" value="{{ $category->id }}">
            <label>Category Name:</label>
            <input type="text" name="categories[{{ $loop->index }}][category_name]" value="{{ $category->category_name }}" required>

            <h4>Violations:</h4>
            @foreach ($category->violationTypes as $violation)
                <input type="hidden" name="categories[{{ $loop->parent->index }}][violationTypes][{{ $loop->index }}][id]" value="{{ $violation->id }}">
                <label>Violation Name:</label>
                <input type="text" name="categories[{{ $loop->parent->index }}][violationTypes][{{ $loop->index }}][violation_name]" value="{{ $violation->violation_name }}" required>
                <br>
            @endforeach
        </div>
    @endforeach

    <button type="submit">Save Manual</button>
</form>
@endsection
