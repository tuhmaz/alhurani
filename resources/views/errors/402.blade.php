@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutFront')

@section('title', __('Payment Required'))

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
<style>
    @media (max-width: 576px) {
        .fs-xxlarge {
            font-size: 4rem !important;
            line-height: 4rem !important;
        }
        .misc-wrapper h4 {
            font-size: 1.25rem;
        }
        .misc-wrapper p {
            font-size: 0.9rem;
        }
        .img-fluid {
            max-width: 150px;
        }
    }
    
    @media (max-width: 375px) {
        .fs-xxlarge {
            font-size: 3rem !important;
            line-height: 3rem !important;
        }
    }
    
    .misc-wrapper {
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .container-xxl {
        max-width: 100%;
        padding-left: 1rem;
        padding-right: 1rem;
    }
</style>
@endsection


@section('content')
<!-- Error -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2 fs-xxlarge" style="line-height: 6rem; font-size: 6rem;">402</h1>
    <h4 class="mb-2 mx-2">{{ __('Payment Required') }} ⚠️</h4>
    <p class="mb-6 mx-2">{{ __('You must pay for access to this content.') }}</p>
    <a href="{{url('/')}}" class="btn btn-primary mb-10">{{ __('Back to home') }}</a>
    <div class="mt-4">
      <img src="{{ asset('assets/img/illustrations/payment-required.svg') }}" alt="Payment Required" class="img-fluid" style="max-width: 225px; width: 100%; height: auto;">
    </div>
  </div>
</div>
<div class="container-fluid misc-bg-wrapper">
  <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" class="img-fluid" alt="page-misc-error" style="max-height: 355px; width: 100%; object-fit: cover;" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">
</div>
<!-- /Error -->
@endsection
