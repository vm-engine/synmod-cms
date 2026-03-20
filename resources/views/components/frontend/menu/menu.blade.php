@if ($items->isNotEmpty())
    <nav
        class="cms-menu cms-menu--{{ $position }}"
        aria-label="{{ $position }} navigation"
    >
        <ul class="cms-menu__list">
            @foreach ($items as $item)
                <li class="cms-menu__item {{ $item->children->isNotEmpty() ? 'cms-menu__item--has-children' : '' }}">
                    <a
                        class="cms-menu__link"
                        href="{{ $item->resolveUrl() }}"
                        @if ($item->open_new_tab) target="_blank"
                            rel="noopener noreferrer" @endif
                    >{{ $item->label }}</a>

                    @if ($item->children->isNotEmpty())
                        <ul class="cms-menu__submenu">
                            @foreach ($item->children as $child)
                                <li
                                    class="cms-menu__subitem {{ $child->children->isNotEmpty() ? 'cms-menu__subitem--has-children' : '' }}">
                                    <a
                                        class="cms-menu__sublink"
                                        href="{{ $child->resolveUrl() }}"
                                        @if ($child->open_new_tab) target="_blank"
                                            rel="noopener noreferrer" @endif
                                    >{{ $child->label }}</a>

                                    @if ($child->children->isNotEmpty())
                                        <ul class="cms-menu__submenu cms-menu__submenu--level-2">
                                            @foreach ($child->children as $grandchild)
                                                <li class="cms-menu__subitem cms-menu__subitem--level-2">
                                                    <a
                                                        class="cms-menu__sublink cms-menu__sublink--level-2"
                                                        href="{{ $grandchild->resolveUrl() }}"
                                                        @if ($grandchild->open_new_tab) target="_blank"
                                                            rel="noopener noreferrer" @endif
                                                    >{{ $grandchild->label }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
