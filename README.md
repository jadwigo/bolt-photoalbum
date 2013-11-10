Photo Album
===========

This is an extension for the [Bolt](http://bolt.cm) content management system.

Print links to navigate sequentially through albums with `{{ AlbumNext( record ) }}` and `{{ AlbumPrev( record ) }}`.

You can also load all photos in an album with `{{ AlbumPhotos( record ) }}`.

Use `{{ AlbumNext(record) }}` and `{{ AlbumPrev(record) }}` in your `photo.twig` template and use `{{ AlbumPhotos(record) }}` in your `album.twig` template.

Instead of `record` you can also use the current name for the content type in the template.


AlbumNext(record, true)
=======================
If you use `{{ AlbumNext( record, false ) }}` the tag will be silent, and only the built-in `record.next` will be replaced with the next entry for the album.

Example usage for the `photo.twig` template.
    
    {{ AlbumNext(record, false) }}
    
    {% if record.next.image != "" %}
        <a href="{{ record.next.link() }}" title="{{ record.next.title }}" role="next">
            <span>&raquo;</span>
            <img src="{{ record.next.image|image(100, 100) }}">
        </a>
    {% endif %}


AlbumPrev(record, true)
=======================
If you use `{{ AlbumPrev( record, false ) }}` the tag will be silent, and only the built-in `record.previous` will be replaced with the previous entry for the album.

Example usage for the `photo.twig` template.

    {{ AlbumPrev(record, false) }}
    
    {% if record.previous.image != "" %}
        <a href="{{ record.previous.link() }}" title="{{ record.previous.title }}" role="previous">
            <span>&laquo;</span>
            <img src="{{ record.previous.image|image(100, 100) }}">
        </a>
    {% endif %}


AlbumPhotos(record)
===========================
Loads all photos in an album with `{{ AlbumPhotos( record ) }}`. This tag is always silent. The new related items will be placed in `record.photos`.

Example usage for the `album.twig` template:

    <h2>{{ album.title }}</h2>

    {{ album.teaser }}

    {# You could also use
    {{ AlbumPhotos(album) }}
    {% set photos = album.photos %} #}
    
    {{ AlbumPhotos(record) }}
    {% set photos = record.photos %}
   
    {% if photos is not empty %}
        <ul class="related">
            {% for photo in photos %}
                <li>
                    <a href="{{ photo.link }}">
                        <h3>{{ photo.title }}</h3>
                    {% if photo.image != "" %}
                        <img src="{{ photo.image|thumbnail(200, 200) }}" title="{{ photo.title }}">
                    {% else %}
                        {{ photo.title }}
                    {% endif %}
                    </a>
                </li>
            {%  endfor %}
        </ul>
    {% endif %}


Preparation
===========
Create content types for photos and albums and give them relations and a weight column. It does nor really matter what you name the types and columns, as long as you use those names in de extension `config.yml`.

An example for your content types is:
    
    photos:
        name: Photos
        singular_name: Photo
        fields:
            title:
                type: text
                class: large
            slug:
                type: slug
                uses: title
            image:
                type: image
            weight:
                type: number
        relations:
            albums:
                multiple: false
                label: Select an album
                order: -id
        record_template: photo.twig
        listing_template: listing.twig
        listing_records: 10
        sort: weight ASC
        recordsperpage: 20
        
    albums:
        name: Albums
        singular_name: Album
        fields:
            title:
                type: text
                class: large
            slug:
                type: slug
                uses: title
            image:
                type: image
            weight:
                type: number
        relations:
            photos:
                multiple: true
                label: Select photos
        record_template: album.twig
        listing_template: albums.twig
        listing_records: 10
        sort: weight ASC
        recordsperpage: 20

After you have created some albums and placed some photos in those albums you can use the tags in your templates to create album navigation.

