@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Archive</h1>
    <div class="row">
        @foreach($archives as $archive)
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ $archive->title }}</h5>
                    <p class="card-text">{{ $archive->description }}</p>
                    <button class="btn btn-danger delete-btn" data-id="{{ $archive->id }}">Delete</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const archiveId = this.getAttribute('data-id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the archive.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit a form or send AJAX request to delete
                fetch(`/archive/${archiveId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if(response.ok) {
                        Swal.fire('Deleted!', 'The archive has been deleted.', 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error!', 'Failed to delete.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection