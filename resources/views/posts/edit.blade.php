@extends('layouts.blog')

@section('content')
    @include('posts._form', ['post' => $post])
@endsection
