@extends('layouts.master')

@section('page-header', 'Automation')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-robot-arm"></i> Automate Protection</h3>
                </div>
                <form id="import-log" class="form" action="{{ route('dominion.protection.import-log') }}" method="post">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">Log (from Excel Simulator):</label>
                            <textarea name="log" class="form-control" rows="20">{{ old('log') }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Validate Log</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>You can attempt to import a log from an Excel sim.</p>
                    <p>The following actions are not suported within a single hour:</p>
                    <ul>
                        <li>Construction after destroying factories</li>
                        <li>Construction both before and after daily land bonus</li>
                        <li>Rezoning both before and after daily land bonus</li>
                        <li>Trading for multiple resources</li>
                        <li>Unlocking techs</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection
