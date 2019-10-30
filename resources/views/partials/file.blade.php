<?php
$class = 'info';
if ($file->status & \App\File::STATUS_ADDED) {
    $class = 'info';
} elseif ($file->status & \App\File::STATUS_ANALYSIS) {
    $class = 'info';
} elseif ($file->status & \App\File::STATUS_INPUT_NEEDED) {
    $class = 'primary';
} elseif ($file->status & \App\File::STATUS_READY) {
    $class = 'secondary';
} elseif ($file->status & \App\File::STATUS_RUNNING) {
    $class = 'secondary';
} elseif ($file->status & \App\File::STATUS_STOPPED) {
    $class = 'warning';
} elseif ($file->status & \App\File::STATUS_WHOLE) {
    $class = 'success';
}
?>
<div class="card bg-{{ $class }} mb-3">
    {{--                <img src="..." class="card-img-top" alt="...">--}}
    <div class="card-body">
        <h5 class="card-title">{{ $file->name }}</h5>
        <p class="card-text">{{ $file->message }}</p>
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
