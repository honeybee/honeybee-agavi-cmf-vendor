(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define([], function () {
            return (root.selectrect = factory());
        });
    } else if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory();
    } else {
        // Browser globals
        root.selectrect = factory();
    }
}(this, function () {
    var HANDLE = {
        NW: 1,
        N: 2,
        NE: 3,
        E: 4,
        SE: 5,
        S: 6,
        SW: 7,
        W: 8,
        ALL: 9
    };

    var GRAB_CLICK = 5;
    var GRAB_TOUCH = 10;
    var GRAB = ("ontouchstart" in document.documentElement) ? GRAB_TOUCH : GRAB_CLICK;

    function build_canvas(img) {
        var canvas = document.createElement("canvas");
        canvas.style.top = img.offsetTop + "px";
        canvas.style.left = img.offsetLeft + "px";
        canvas.width = img.offsetWidth;
        canvas.height = img.offsetHeight;
        canvas.style.position = "absolute";
        img.parentElement.appendChild(canvas);

        return canvas;
    }

    var get_element_position = function(element, pos) {
        pos = pos || { x: 0, y: 0 };

        if (!element) {
            return pos;
        }

        pos.x += element.offsetLeft  || 0;
        pos.y += element.offsetTop || 0;

        return get_element_position(element.offsetParent, pos);
    };

    function scale_selection(sel) {
        var s = sel.selection;
        var cx = sel.img.naturalWidth/sel.canvas.width;
        var cy = sel.img.naturalHeight/sel.canvas.height;

        return {
            x: Math.round(s.x * cx),
            y: Math.round(s.y * cy),
            w: Math.round(s.w * cx),
            h: Math.round(s.h * cy)
        };
    }

    function unscale_selection(sel) {
        var s = sel.selection;
        var cx = sel.img.naturalWidth/sel.canvas.width;
        var cy = sel.img.naturalHeight/sel.canvas.height;

        return {
            x: Math.round(s.x / cx),
            y: Math.round(s.y / cy),
            w: Math.round(s.w / cx),
            h: Math.round(s.h / cy)
        };
    }

    function dragstart(sel, x, y) {
        sel.lastpos.x = x;
        sel.lastpos.y = y;
        sel.drag_handle = get_drag_handle(sel, x, y);
        if (!sel.drag_handle) {
            sel.selection = {x:x , y:y, w:0, h:0};
            sel.drag_handle = HANDLE.SW;
        }
        sel.dragging = true;
    }

    function distance(x1, y1, x2, y2) {
        var dx = x2 - x1;
        var dy = y2 - y1;
        return Math.sqrt(dx*dx + dy*dy);
    }

    function get_drag_handle(sel, x, y) {
        var s = sel.selection;
        if (inside(s, x, y)) {
            return HANDLE.ALL;
        }

        if (distance(x,y, s.x, s.y) < GRAB) {
            return HANDLE.NW;
        }

        if (distance(x,y, s.x + s.w/2, s.y) < GRAB) {
            return HANDLE.N;
        }

        if (distance(x,y, s.x + s.w, s.y) < GRAB) {
            return HANDLE.NE;
        }

        if (distance(x,y, s.x + s.w, s.y + s.h/2) < GRAB) {
            return HANDLE.E;
        }

        if (distance(x,y, s.x + s.w, s.y + s.h) < GRAB) {
            return HANDLE.SE;
        }

        if (distance(x,y, s.x + s.w/2, s.y + s.h) < GRAB) {
            return HANDLE.S;
        }

        if (distance(x,y, s.x, s.y + s.h) < GRAB) {
            return HANDLE.SW;
        }

        if (distance(x,y, s.x, s.y + s.h/2) < GRAB) {
            return HANDLE.W;
        }


    }

    function dragend(sel, x, y) {
        normalize(sel);
        sel.dragging = false;
        sel.drag_handle = null;
        var s = scale_selection(sel);
        sel.update(s);
        render(sel);
    }

    function drag(sel, x, y) {
        var s = sel.selection;
        var dx = x - sel.lastpos.x;
        var dy = y - sel.lastpos.y;
        sel.lastpos = {x: x, y: y};
        switch (sel.drag_handle) {
            case HANDLE.NW:
                s.x += dx;
            s.w -= dx;
            s.y += dy;
            s.h -= dy;
            break;
            case HANDLE.N:
                s.y += dy;
            s.h -= dy;
            break;
            case HANDLE.NE:
                s.w += dx;
            s.y += dy;
            s.h -= dy;
            break;
            case HANDLE.E:
                s.w += dx;
            break;
            case HANDLE.SE:
                s.w += dx;
            s.h += dy;
            break;
            case HANDLE.S:
                s.h += dy;
            break;
            case HANDLE.SW:
                s.x += dx;
            s.w -= dx;
            s.h += dy;
            break;
            case HANDLE.W:
                s.x += dx;
            s.w -= dx;
            break;
            case HANDLE.ALL:
                s.x += dx;
            s.y += dy;
            break;
        }
        keep_bounds(sel);
        window.requestAnimationFrame(render.bind(this, sel));
    }

    function keep_bounds(sel) {
        var w = sel.canvas.width;
        var h = sel.canvas.height;
        var s = sel.selection;

        s.x = s.x + s.w > w ? w - s.w : s.x;
        s.y = s.y + s.h > h ? h - s.h : s.y;
        s.x = s.x < 0 ? 0 : s.x;
        s.y = s.y < 0 ? 0 : s.y;
    }

    function normalize(sel) {
        var s = sel.selection;
        if (s.w < 0) {
            s.x += s.w;
            s.w *= -1;
        }
        if (s.h < 0) {
            s.y += s.h;
            s.h *= -1;
        }
    }

    function inside(rect, x, y) {
        return (x >= rect.x + GRAB) &&
            (x <= rect.x + rect.w - GRAB) &&
            (y >= rect.y + GRAB) &&
            (y <= rect.y + rect.h - GRAB);
    }

    function set_cursor(sel, x, y) {
        var cursor;
        var handle;
        if (sel.drag_handle) {
            handle = sel.drag_handle;
        } else {
            handle = get_drag_handle(sel, x, y);
        }

        switch (handle) {

            case HANDLE.W:
                case HANDLE.E:
                cursor = "ew-resize";
            break;
            case HANDLE.NW:
                case HANDLE.SE:
                cursor = "nwse-resize";
            break;
            case HANDLE.N:
                case HANDLE.S:
                cursor = "ns-resize";
            break;
            case HANDLE.NE:
                case HANDLE.SW:
                cursor = "nesw-resize";
            break;
            case HANDLE.ALL:
                cursor = "move";
            break;
            default:
                cursor = "crosshair";
        }
        sel.canvas.style.cursor = cursor;
    }

    function render(sel) {
        var s = sel.selection;
        if (!s) {
            return;
        }
        // round off any fractions to ensure clean rendering without blurry lines.
        var x = Math.round(s.x);
        var y = Math.round(s.y);
        var w = Math.round(s.w);
        var w2 = Math.round(w/2);
        var h = Math.round(s.h);
        var h2 = Math.round(h/2);
        var wmax = sel.canvas.width;
        var hmax = sel.canvas.height;

        var ctx = sel.canvas.getContext("2d");
        ctx.clearRect(0,0, wmax, hmax);

        ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
        ctx.fillRect(0, 0, wmax, hmax);

        ctx.clearRect(x, y, w, h);

        ctx.strokeStyle = "rgb(255,255,255)";
        ctx.lineWidth = 1;
        // the 0.5 makes the path line up with pixel centers
        ctx.strokeRect(x + 0.5, y + 0.5, w, h);

        ctx.fillStyle = "rgb(0, 0, 0)";
        ctx.fillRect(x - GRAB, y - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x + w2 - GRAB, y - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x + w - GRAB, y - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x + w - GRAB, y + h2 - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x + w - GRAB, y + h - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x + w2 - GRAB, y + h - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x - GRAB, y + h - GRAB, 2*GRAB, 2*GRAB);
        ctx.fillRect(x - GRAB, y + h2 - GRAB, 2*GRAB, 2*GRAB);
    }

    function cancel() {
        this.update = null;
        this.canvas.parentElement.removeChild(this.canvas);
    }

    function on_update(cb) {
        this.update = cb;
        this.update(scale_selection(this));
    }

    function correct_coordinates(sel, ev) {
        var x;
        var y;
        var w = sel.canvas.width;
        var h = sel.canvas.height;

        var canvas_position = get_element_position(sel.canvas);

        if (ev.touches && ev.touches.length > 0) {
            x = ev.touches[0].pageX - canvas_position.x;
            y = ev.touches[0].pageY - canvas_position.y;
        } else if (typeof ev.offsetX !== "undefined") {
            x = ev.offsetX;
            y = ev.offsetY;
        } else {
            x = sel.lastpos.x;
            y = sel.lastpos.y;
        }

        x = x > w ? w : (x < 0 ? 0 : x);
        y = y > h ? h : (y < 0 ? 0 : y);

        return {x: x, y: y};
    }

    return function(img, preset) {
        preset = preset || { x: -100, y: -100, w: 10, h: 10};

        var sel = {
            img: img,
            canvas: build_canvas(img),
            dragging: false,
            lastpos: {
                x: null,
                y: null
            },
            selection: preset,
            cancel: cancel,
            update: function(){},
            onUpdate: on_update
        };

        sel.selection = unscale_selection(sel);
        console.log(sel);

        function pointerdown(ev) {
            console.log(ev);
            ev.stopPropagation();
            ev.preventDefault();
            var coords = correct_coordinates(sel, ev);
            dragstart(sel, coords.x, coords.y);
        }
        function pointerup(ev) {
            ev.stopPropagation();
            ev.preventDefault();
            var coords = correct_coordinates(sel, ev);
            dragend(sel, coords.x, coords.y);
        }
        function pointermove(ev) {
            ev.stopPropagation();
            ev.preventDefault();
            var coords = correct_coordinates(sel, ev);
            set_cursor(sel, coords.x, coords.y);
            if (sel.dragging) {
                drag(sel, coords.x, coords.y);
            } else {

            }
        }

        sel.canvas.addEventListener("mousedown", pointerdown);
        sel.canvas.addEventListener("touchstart", pointerdown);
        sel.canvas.addEventListener("mouseup", pointerup);
        sel.canvas.addEventListener("touchend", pointerup);
        sel.canvas.addEventListener("mousemove", pointermove);
        sel.canvas.addEventListener("touchmove", pointermove);

        window.requestAnimationFrame(render.bind(this, sel));

        return sel;
    };
}));
