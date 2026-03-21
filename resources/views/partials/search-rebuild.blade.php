<div class="admin-footnote">
    <span>Nếu data tìm kiếm bị lệch,</span>
    <form method="POST" action="{{ route('search.rebuild') }}" class="inline-form admin-footnote-form">
        @csrf
        <button type="submit" class="text-link-button">nhấn vào đây</button>
    </form>
    <span>để rebuild index tìm kiếm</span>
</div>
