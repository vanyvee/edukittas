// store/getVisibleFields.js

export const getVisibleFields = ({
  fields,
  targetObject,
  currentUser,
  defaultVisibleFields = [],
}) => {
  const isOwner = currentUser.id === targetObject.id
  const isAdmin = currentUser.role === 'admin'
  const isTeacher = currentUser.role === 'teacher'
  const isStudent = targetObject.role === 'student'
  const isTeacherViewingStudent = isTeacher && isStudent

  if (isAdmin || isOwner || isTeacherViewingStudent) {
    return fields
  }

  const allowedKeys = [...new Set([...(targetObject.publicview || []), ...defaultVisibleFields])]
  return fields.filter(field => allowedKeys.includes(field.key))
}

export default getVisibleFields
