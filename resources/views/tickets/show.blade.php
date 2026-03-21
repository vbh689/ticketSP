@extends('layouts.app')

@section('title', $ticket->ticket_code . ' | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>{{ $ticket->ticket_code }} - {{ $ticket->title }}</h1>
                    <p>{{ $isReadOnly ? 'Chế độ xem read-only qua link chia sẻ.' : 'Theo dõi, xử lý và ghi nhận lịch sử ticket.' }}</p>
                </div>
            </div>

            <div class="nav">
                @if (! $isReadOnly)
                    @if (auth()->user()?->is_manager)
                        <a class="button button-muted" href="{{ route('employees.index') }}">Nhân viên</a>
                    @endif
                    <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại backlog</a>
                @endif
            </div>
        </header>

        @if (session('status'))
            <div class="flash flash-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        @if ($isReadOnly)
            <div class="helper">
                Bạn đang xem ticket bằng `view_key`, nên chỉ có quyền đọc. Các thao tác cập nhật được khóa hoàn toàn.
            </div>
        @endif

        <section class="grid grid-3">
            <article class="card panel stack" style="grid-column: span 2;">
                <div>
                    <h2 class="section-title">Thông tin chính</h2>
                    <p class="section-copy">Toàn bộ nội dung gốc của yêu cầu hỗ trợ và trạng thái hiện tại.</p>
                </div>

                <div class="grid grid-2">
                    <div>
                        <div class="meta">Khách hàng</div>
                        <strong>{{ $ticket->requester_name }}</strong>
                        <div>{{ $ticket->requester_contact ?: 'Chưa có thông tin liên hệ' }}</div>
                    </div>
                    <div>
                        <div class="meta">Loại ticket</div>
                        <strong>{{ $ticket->category->name }}</strong>
                    </div>
                    <div>
                        <div class="meta">Người tạo</div>
                        <strong>{{ $ticket->creator->name }}</strong>
                    </div>
                    <div>
                        <div class="meta">Người phụ trách</div>
                        @php($relatedHandlers = $ticket->relatedHandlers())
                        @if ($relatedHandlers->isNotEmpty())
                            <div class="people-list">
                                @foreach ($relatedHandlers as $handler)
                                    <span class="person-chip">{{ $handler->display_name }}</span>
                                @endforeach
                            </div>
                            @if ($ticket->assignee)
                                <div class="inline-note">Phụ trách chính: {{ $ticket->assignee->display_name }}</div>
                            @endif
                        @else
                            <strong>Chưa có người nhận</strong>
                        @endif
                    </div>
                    <div>
                        <div class="meta">Trạng thái</div>
                        @include('tickets.partials.status-badge', ['status' => $ticket->status])
                    </div>
                    <div>
                        <div class="meta">Link chia sẻ read-only</div>
                        <div class="copy-share">
                            <button
                                type="button"
                                class="button button-secondary"
                                data-copy-text="{{ $shareUrl }}"
                                data-copy-default="Copy link"
                                data-copy-success="Đã copy"
                            >
                                Copy link
                            </button>
                        </div>
                        <div class="inline-note">Dùng nút copy để chia sẻ link xem read-only cho khách hàng.</div>
                    </div>
                </div>

                <div>
                    <div class="meta">Mô tả</div>
                    <p style="white-space: pre-line;">{{ $ticket->description }}</p>
                </div>
            </article>

            <aside class="card panel stack">
                <div>
                    <h2 class="section-title">Điều khiển</h2>
                    <p class="section-copy">Các thao tác xử lý nhanh dành cho support đã đăng nhập.</p>
                </div>

                @if ($isReadOnly)
                    <div class="helper">Người xem không đăng nhập chỉ có thể đọc thông tin ticket.</div>
                @else
                    @if (! $ticket->assignee_id)
                        <form method="POST" action="{{ route('tickets.claim', $ticket) }}">
                            @csrf
                            <button class="button-primary" type="submit">Nhận ticket này</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('tickets.status.update', $ticket) }}" class="stack">
                        @csrf
                        @method('PATCH')

                        <label>
                            Cập nhật trạng thái
                            <select name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($ticket->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button class="button-secondary" type="submit">Lưu trạng thái</button>
                    </form>
                @endif

                <div class="comment">
                    <div class="meta">Dấu thời gian</div>
                    <strong>Tạo lúc {{ $ticket->created_at->format('d/m/Y H:i') }}</strong>
                    <div>Resolved: {{ $ticket->resolved_at?->format('d/m/Y H:i') ?? 'Chưa có' }}</div>
                    <div>Closed: {{ $ticket->closed_at?->format('d/m/Y H:i') ?? 'Chưa có' }}</div>
                </div>
            </aside>
        </section>

        <section class="grid grid-2">
            <article class="card panel stack">
                <div>
                    <h2 class="section-title">Ghi chú xử lý</h2>
                    <p class="section-copy">Dùng để lưu diễn biến làm việc trong quá trình support.</p>
                </div>

                @if (! $isReadOnly)
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="stack">
                        @csrf
                        <label>
                            Nội dung ghi chú
                            <textarea name="content" required>{{ old('content') }}</textarea>
                        </label>

                        <button class="button-primary" type="submit">Thêm ghi chú</button>
                    </form>
                @endif

                <div class="list">
                    @forelse ($ticket->comments as $comment)
                        <article class="comment">
                            <strong>{{ $comment->author->name }}</strong>
                            <div class="meta">{{ $comment->created_at->format('d/m/Y H:i') }}</div>
                            <p style="margin-bottom: 0; white-space: pre-line;">{{ $comment->content }}</p>
                        </article>
                    @empty
                        <div class="empty">Chưa có ghi chú xử lý nào.</div>
                    @endforelse
                </div>
            </article>

            <article class="card panel stack">
                <div>
                    <h2 class="section-title">Lịch sử hoạt động</h2>
                    <p class="section-copy">Audit trail cơ bản cho các hành động vận hành quan trọng.</p>
                </div>

                <div class="list">
                    @forelse ($ticket->activities as $activity)
                        <article class="activity">
                            <strong>{{ $activity->action_detail }}</strong>
                            <div class="meta">
                                @if ($activity->actor)
                                    {{ $activity->actor->display_name }} · {{ $activity->actor->username }}
                                @else
                                    Hệ thống
                                @endif
                                · {{ $activity->created_at->format('d/m/Y H:i') }}
                            </div>
                        </article>
                    @empty
                        <div class="empty">Chưa có hoạt động nào được ghi nhận.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const copyButton = document.querySelector('[data-copy-text]');

                if (! copyButton) {
                    return;
                }

                copyButton.addEventListener('click', async () => {
                    const defaultText = copyButton.dataset.copyDefault || 'Copy';
                    const successText = copyButton.dataset.copySuccess || 'Copied';

                    try {
                        await navigator.clipboard.writeText(copyButton.dataset.copyText || '');
                        copyButton.textContent = successText;
                    } catch (error) {
                        copyButton.textContent = 'Không thể copy';
                    }

                    window.setTimeout(() => {
                        copyButton.textContent = defaultText;
                    }, 1600);
                });
            });
        </script>
    </main>
@endsection
