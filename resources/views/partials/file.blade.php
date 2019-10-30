<?php
$class  = 'secondary';
$action = '';
if ($file->status & \App\File::STATUS_ADDED) {
    $class  = 'secondary';
    $action = 'Upload Successful';
} elseif ($file->status & \App\File::STATUS_ANALYSIS) {
    $class  = 'secondary';
    $action = 'Analyzing';
} elseif ($file->status & \App\File::STATUS_INPUT_NEEDED) {
    $class  = 'info';
    $action = 'Input Needed';
} elseif ($file->status & \App\File::STATUS_READY) {
    $class  = 'secondary';
    $action = 'Ready';
} elseif ($file->status & \App\File::STATUS_RUNNING) {
    $class  = 'secondary';
    $action = 'Processing';
} elseif ($file->status & \App\File::STATUS_STOPPED) {
    $class  = 'danger';
    $action = 'Cancelled';
} elseif ($file->status & \App\File::STATUS_WHOLE) {
    $class  = 'success';
    $action = 'Complete';
}
?>
<div class="card border-{{ $class }} mb-4">

    <a href="#file{{ $file->id }}" class="card-header text-{{ $class }}" role="tab" id="heading{{ $file->id }}" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="file{{ $file->id }}">
        {{ $file->name }}
        <i class="fa fa-chevron-down pull-right"></i>
        <span class="pull-right">{{ $action }}</span>
    </a>

    <div id="file{{ $file->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $file->id }}">
        <div class="card-body">
            <p class="card-text">{{ $file->message }}</p>
        </div>
    </div>

    <form>
        @csrf
        {{--                    --}}
        {{--                    <div class="form-group">--}}
        {{--                        <label for="exampleInputEmail1">Email address</label>--}}
        {{--                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">--}}
        {{--                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>--}}
        {{--                    </div>--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label for="exampleInputPassword1">Password</label>--}}
        {{--                        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">--}}
        {{--                    </div>--}}
        {{--                    <div class="form-check">--}}
        {{--                        <input type="checkbox" class="form-check-input" id="exampleCheck1">--}}
        {{--                        <label class="form-check-label" for="exampleCheck1">Check me out</label>--}}
        {{--                    </div>--}}
        {{--                    <button type="submit" class="btn btn-primary">Submit</button>--}}
    </form>
    {{--                <ul class="list-group list-group-flush">--}}
    {{--                    <li class="list-group-item">Cras justo odio</li>--}}
    {{--                    <li class="list-group-item">Dapibus ac facilisis in</li>--}}
    {{--                    <li class="list-group-item">Vestibulum at eros</li>--}}
    {{--                </ul>--}}
    {{--                <div class="card-body">--}}
    {{--                    <a href="#" class="card-link">Card link</a>--}}
    {{--                    <a href="#" class="card-link">Another link</a>--}}
    {{--                </div>--}}
</div>
