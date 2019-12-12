<a class="dropdown-item @if(empty($notification->read_at)) text-warning @endif @if(empty($notification->data['url'])) d-none @endif" href="{{ $notification->data['url'] ?? '#' }}">
    <i class="fa fa-{{ $notification->data['icon'] ?? 'info' }}"></i>
    <span class="message">{{ $notification->data['message'] ?? '' }}</span>
</a>
