school
 id
 schoolId uuid
 schoolCode
 dirId


once a director is created it can't be deleted except
by edukitas, but director can delete any model
Directors
 id
 dirId uuid uniqie
 phone


user
 id
 userId uuid unique
 regNo concat schoolCode/month/11111
 role
 
Students
 id
 regNum concat schoolCode/month/345467
 stuId
 parentId
 currrentClass
 dob
 address

Parent
 id
 parentId
 address