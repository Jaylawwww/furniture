#!/usr/bin/env python3
"""Replace duplicated admin sidebars with shared partial."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "templates"

PATTERN = re.compile(
    r"    <!-- Sidebar Navigation -->\s*\n    <aside class=\"sidebar\">.*?</aside>\s*\n",
    re.DOTALL,
)

SIMPLE = {
    "admin/reports.html.twig": "reports",
    "admin/search_results.html.twig": "dashboard",
    "user/index.html.twig": "users",
    "user/show.html.twig": "users",
    "user/new.html.twig": "users",
    "user/edit.html.twig": "users",
}

PRODUCT = """    {% if isAdmin %}
        {% include '_partials/admin_sidebar.html.twig' with { active: 'products' } %}
    {% else %}
        {% include '_partials/staff_sidebar.html.twig' with { active: 'products' } %}
    {% endif %}

"""

CATEGORY = """    {% if isAdmin %}
        {% include '_partials/admin_sidebar.html.twig' with { active: 'categories' } %}
    {% else %}
        {% include '_partials/staff_sidebar.html.twig' with { active: 'categories' } %}
    {% endif %}

"""


def main() -> None:
    for rel, active in SIMPLE.items():
        path = ROOT / rel
        if not path.exists():
            continue
        text = path.read_text(encoding="utf-8")
        if "_partials/admin_sidebar" in text:
            print("skip", rel)
            continue
        m = PATTERN.search(text)
        if not m:
            print("no match", rel)
            continue
        repl = f"    {{% include '_partials/admin_sidebar.html.twig' with {{ active: '{active}' }} %}}\n\n"
        path.write_text(text[: m.start()] + repl + text[m.end() :], encoding="utf-8")
        print("updated", rel)

    for name in ["index", "show", "new", "edit"]:
        for folder, block in [("product", PRODUCT), ("category", CATEGORY)]:
            path = ROOT / folder / f"{name}.html.twig"
            if not path.exists():
                continue
            text = path.read_text(encoding="utf-8")
            if "_partials/admin_sidebar" in text:
                print("skip", path.relative_to(ROOT))
                continue
            m = PATTERN.search(text)
            if not m:
                print("no match", path.relative_to(ROOT))
                continue
            path.write_text(text[: m.start()] + block + text[m.end() :], encoding="utf-8")
            print("updated", path.relative_to(ROOT))


if __name__ == "__main__":
    main()
