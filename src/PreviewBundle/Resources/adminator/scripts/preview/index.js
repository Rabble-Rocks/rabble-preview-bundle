import React, {Component, Fragment} from 'react';
import ReactDOM from "react-dom";

class Preview extends Component {
    constructor(props) {
        super(props);
        this.state = {
            previewUrl: null
        }
        this.savePreview();
    }

    savePreview() {
        let formData = new FormData(this.props.form);
        formData.append('contentType', this.props.contentType);
        formData.append('content', this.props.contentId);
        fetch(
            this.props.saveUrl,
            {
                method: 'POST',
                body: formData,
            }
        )
            .then((response) => response.json())
            .then((result) => {
                if (typeof result.uuid !== 'undefined') {
                    let Routing = window.Routing;
                    this.setState({
                        previewUrl: Routing.generate('rabble_admin_preview', {
                            content: result.uuid,
                            _content_locale: this.props.locale,
                            t: (new Date()).getTime()
                        })
                    });
                    this.forceUpdate();
                }
            })
            .catch((error) => {
                console.error('Error:');
            });
    }

    render() {
        let iframe = '';
        if (null !== this.state.previewUrl) {
            iframe = <iframe src={this.state.previewUrl} className="w-100" style={{height: 'calc(100vh - 340px)'}}/>;
        }
        return (
            <Fragment>
                <button className="btn btn-primary float-end" type="button" onClick={() => this.savePreview()}>
                    <i className="fa fa-refresh" /> Reload
                </button>
                <h2 className="c-grey-900 mB-20">Preview</h2>
                {iframe}
            </Fragment>
        );
    }
}
let preview = document.getElementById('rabble_content_preview');
if (null !== preview) {
    let form = document.querySelector("form[name=content_form]");
    ReactDOM.render(
        (<Preview saveUrl={preview.dataset.save} contentType={preview.dataset.contentType} contentId={preview.dataset.contentId} locale={preview.dataset.locale} form={form}/>),
        preview
    );
}
