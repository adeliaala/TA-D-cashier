@extends('layouts.app')

@section('title')
    Branch Details
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('branch.edit', $branch->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('branch.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Branch Name</th>
                                    <td>{{ $branch->name }}</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td>{{ $branch->address }}</td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>{{ $branch->phone }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ $branch->email }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if($branch->status)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $branch->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td>{{ $branch->updated_at->format('d M Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 