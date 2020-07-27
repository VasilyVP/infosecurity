/** Класс автоматического переориентирования изображения, изменения размера и уменьшения качества */
export default class ImageManipulation {

    /** Возвращает Promise со значением ориентации изображения jpeg
     * Варианты: "-2" - not jpeg, "-1" - not defined
     * https://stackoverflow.com/questions/7584794/accessing-jpeg-exif-rotation-data-in-javascript-on-the-client-side
     */
    getOrientationPr(file) {
        return new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const view = new DataView(e.target.result);
                if (view.getUint16(0, false) != 0xFFD8) {
                    resolve(-2);
                }
                const length = view.byteLength;
                let offset = 2;
                while (offset < length) {
                    if (view.getUint16(offset + 2, false) <= 8) resolve(-1);
                    const marker = view.getUint16(offset, false);
                    offset += 2;
                    if (marker == 0xFFE1) {
                        if (view.getUint32(offset += 2, false) != 0x45786966) {
                            resolve(-1);
                        }

                        const little = view.getUint16(offset += 6, false) == 0x4949;
                        offset += view.getUint32(offset + 4, little);
                        const tags = view.getUint16(offset, little);
                        offset += 2;
                        for (let i = 0; i < tags; i++) {
                            if (view.getUint16(offset + (i * 12), little) == 0x0112) {
                                resolve(view.getUint16(offset + (i * 12) + 8, little));
                            }
                        }
                    }
                    else if ((marker & 0xFF00) != 0xFF00) {
                        break;
                    }
                    else {
                        offset += view.getUint16(offset, false);
                    }
                }
                resolve(-1);
            };
            reader.readAsArrayBuffer(file);
        });
    }

    /** Возвращает контекст элемента 'canvas' с заданными высотой, шириной и ориентацией */
    createNewImage(img, width, height, orientation) {
        let rotate, angle, translate;
        const elem = document.createElement('canvas');
        // формируем параметры изображения по ориентации, чтобы сформировать изображение правильно
        switch (orientation) {
            case 1:
                rotate = false;

                elem.width = width;
                elem.height = height;
                break;
            case 8:
                rotate = true;
                angle = - Math.PI / 2;
                translate = { x: -width, y: 0 };

                elem.width = height;
                elem.height = width;
                break;
            case 3:
                rotate = true;
                angle = Math.PI;
                translate = { x: -width, y: -height };

                elem.width = width;
                elem.height = height;
                break;
            case 6:
                rotate = true;
                angle = Math.PI / 2;
                translate = { x: 0, y: -height };

                elem.width = height;
                elem.height = width;
                break;
            default:
                rotate = false;

                elem.width = width;
                elem.height = height;
                break;
        }
        const ctx = elem.getContext('2d');
        // формируем канву
        if (rotate) {
            ctx.rotate(angle);
            ctx.translate(translate.x, translate.y);
        }
        // формируем новое изображение
        ctx.drawImage(img, 0, 0, width, height);

        return ctx;
    }

    /** возвращает Promise c сжатым файлом изображения до размеров imgSize = {width, height, maxSide} и требуемого качества (0-1) */
    compressImagePr(file, imgSize, quality) {
        const thisObj = this;
        return new Promise(resolve => {
            // получаем исходные размеры изображения и определяем новые
            const reader = new FileReader();
            reader.readAsDataURL(file.file);
            reader.onload = event => {
                const img = new Image();
                img.src = event.target.result;

                img.onload = () => {
                    // определяем размеры новой картинки
                    const ratio = img.naturalWidth / img.naturalHeight;
                    let width, height;
                    // если задан лимитирующий размер
                    if (imgSize.maxSide || false) {
                        const resizeRatio = 
                            (ratio >= 1) ? imgSize.maxSide / img.naturalWidth : imgSize.maxSide / img.naturalHeight;
                        width = img.naturalWidth * resizeRatio;
                        height = img.naturalHeight * resizeRatio;
                    } else {
                        // если задан один из размеров
                        width = imgSize.width || ratio * imgSize.height;
                        height = imgSize.height || imgSize.width / ratio;
                    }
                    
                    // получаем ориентацию
                    thisObj.getOrientationPr(file.file).then(orientation => {
                        // формируем новое изображение
                        const ctx = thisObj.createNewImage(img, width, height, orientation);
                        // и записываем в файл
                        ctx.canvas.toBlob((blob) => {
                            const newFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(newFile);
                        }, 'image/jpeg', quality);
                    });
                }
            }
        });
    }

}