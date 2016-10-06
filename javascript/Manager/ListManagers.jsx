'use strict'
import React from 'react'
import ManagerRow from './ManagerRow.jsx'

class ListManagers extends React.Component {
  constructor(props) {
    super(props)
  }

  render() {
    let listRows = null
    if (!this.props.managers || this.props.managers.length === 0) {
      return <h2>No managers found.</h2>
    } else {
      listRows = this.props.managers.map(function (value, key) {
        return <ManagerRow
          key={key}
          {...value}
          showProperties={this.props.showProperties}
          fillForm={this.props.fillForm.bind(this, value)}
          reload={this.props.reload.bind(this, key)}
          remove={this.props.remove.bind(this, key)}
          message={this.props.message}
          admin={this.props.admin}/>
      }.bind(this))
    }
    return (
      <div>
        {listRows}
      </div>
    )
  }
}

ListManagers.propTypes = {
  managers: React.PropTypes.array,
  fillForm: React.PropTypes.func,
  reload: React.PropTypes.func,
  showProperties: React.PropTypes.func,
  message: React.PropTypes.func,
  remove: React.PropTypes.func,
  admin: React.PropTypes.bool
}

export default ListManagers
