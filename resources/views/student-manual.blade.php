@extends('layouts.educator')

@section('title', 'Student Violation Manual')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/student-manual.css') }}">
@endsection

@section('content')
    <div class="container">
        <div class="main-heading">
            <img src="{{ asset('images/PN-logo-removebg-preview.png') }}" alt="" style="width: 200px; height: 200px; display: block; margin: auto;">
            <h1 style="text-align: center;">PN Student Violation Manual</h1>
        </div>
        <h2 style="text-align: center;">Empowering Responsible Center Life Through Awareness and Discipline.</h2>
        <p>Welcome, scholars! This manual helps you understand the rules and expectations while living at the center. Staying informed is the first step to success and harmony!</p>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                // Highlight the success message
                document.addEventListener('DOMContentLoaded', function() {
                    const alertElement = document.querySelector('.alert-success');
                    if (alertElement) {
                        alertElement.style.animation = 'fadeInOut 5s';
                    }
                });
            </script>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <style>
            @keyframes fadeInOut {
                0% { opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 1; }
                100% { opacity: 0; }
            }
        </style>

        <div class="violation-table">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Violation Categories and Penalties</h3>
                @if(auth()->user()->roles->where('name', 'admin')->isNotEmpty() || auth()->user()->roles->where('name', 'educator')->isNotEmpty())
                    <a href="{{ route('educator.manual.edit') }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Manual
                    </a>
                @endif
            </div>

            @foreach($categories as $index => $category)
            <div class="category-section">
                <h4 class="category-title" data-number="{{ $loop->iteration }}">{{ $category->category_name }}</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Violation Name</th>
                            <th>Severity</th>
                            <th>Offenses</th>
                            <th>Penalties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->violationTypes as $typeIndex => $type)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $type->violation_name }}</td>
                            <td>
                                @switch($type->default_penalty)
                                    @case('W')
                                        Low
                                        @break
                                    @case('VW')
                                        Medium
                                        @break
                                    @case('WW')
                                        High
                                        @break
                                    @case('Pro')
                                        High
                                        @break
                                    @case('Exp')
                                        Very High
                                        @break
                                    @default
                                        Medium
                                @endswitch
                            </td>
                            <td>
                                {{ $type->offenses ?? '1st, 2nd, 3rd' }}
                            </td>
                            <td>
                                @if($type->description)
                                    {!! nl2br(e($type->description)) !!}
                                @else
                                    @switch($type->default_penalty)
                                        @case('W')
                                            1st: Warning<br>
                                            2nd: Verbal Warning<br>
                                            3rd: Written Warning
                                            @break
                                        @case('VW')
                                            1st: Verbal Warning<br>
                                            2nd: Written Warning<br>
                                            3rd: Probation
                                            @break
                                        @case('WW')
                                            1st: Written Warning<br>
                                            2nd: Probation<br>
                                            3rd: Expulsion
                                            @break
                                        @case('Pro')
                                            1st: Probation<br>
                                            2nd: Expulsion
                                            @break
                                        @case('Exp')
                                            Immediate Expulsion
                                            @break
                                        @default
                                            {{ $type->default_penalty }}
                                    @endswitch
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
@endsection