@extends('layouts.app')

@section('title')
    Branches
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branches</h3>
                    @can('create_branches')
                    <div class="card-tools">
                        <a href="{{ route('branch.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Branch
                        </a>
                    </div>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    <tr>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->address }}</td>
                                        <td>{{ $branch->phone }}</td>
                                        <td>{{ $branch->email }}</td>
                                        <td>
                                            @if($branch->status)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('branch.show', $branch->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('edit_branches')
                                            <a href="{{ route('branch.edit', $branch->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('delete_branches')
                                            <form action="{{ route('branch.destroy', $branch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No branches found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $branches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 