<?php
// This file was auto-generated from sdk-root/src/data/connect/2017-08-08/docs-2.json
return [ 'version' => '2.0', 'service' => '<p>The Amazon Connect API Reference provides descriptions, syntax, and usage examples for each of the Amazon Connect actions, data types, parameters, and errors. Amazon Connect is a cloud-based contact center solution that makes it easy to set up and manage a customer contact center and provide reliable customer engagement at any scale.</p>', 'operations' => [ 'CreateUser' => '<p>Creates a new user account in your Amazon Connect instance.</p>', 'DeleteUser' => '<p>Deletes a user account from Amazon Connect.</p>', 'DescribeUser' => '<p>Returns a <code>User</code> object that contains information about the user account specified by the <code>UserId</code>.</p>', 'DescribeUserHierarchyGroup' => '<p>Returns a <code>HierarchyGroup</code> object that includes information about a hierarchy group in your instance.</p>', 'DescribeUserHierarchyStructure' => '<p>Returns a <code>HiearchyGroupStructure</code> object, which contains data about the levels in the agent hierarchy.</p>', 'GetFederationToken' => '<p>Retrieves a token for federation.</p>', 'ListRoutingProfiles' => '<p>Returns an array of <code>RoutingProfileSummary</code> objects that includes information about the routing profiles in your instance.</p>', 'ListSecurityProfiles' => '<p>Returns an array of SecurityProfileSummary objects that contain information about the security profiles in your instance, including the ARN, Id, and Name of the security profile.</p>', 'ListUserHierarchyGroups' => '<p>Returns a <code>UserHierarchyGroupSummaryList</code>, which is an array of <code>HierarchyGroupSummary</code> objects that contain information about the hierarchy groups in your instance.</p>', 'ListUsers' => '<p>Returns a <code>UserSummaryList</code>, which is an array of <code>UserSummary</code> objects.</p>', 'StartOutboundVoiceContact' => '<p>The <code>StartOutboundVoiceContact</code> operation initiates a contact flow to place an outbound call to a customer.</p> <p>There is a throttling limit placed on usage of the API that includes a RateLimit of 2 per second, and a BurstLimit of 5 per second.</p> <p>If you are using an IAM account, it must have permission to the <code>connect:StartOutboundVoiceContact</code> action.</p>', 'StopContact' => '<p>Ends the contact initiated by the <code>StartOutboundVoiceContact</code> operation.</p> <p>If you are using an IAM account, it must have permission to the <code>connect:StopContact</code> action.</p>', 'UpdateContactAttributes' => '<p>The <code>UpdateContactAttributes</code> operation lets you programmatically create new or update existing contact attributes associated with a contact. You can use the operation to add or update attributes for both ongoing and completed contacts. For example, you can update the customer\'s name or the reason the customer called while the call is active, or add notes about steps that the agent took during the call that are displayed to the next agent that takes the call. You can also use the <code>UpdateContactAttributes</code> operation to update attributes for a contact using data from your CRM application and save the data with the contact in Amazon Connect. You could also flag calls for additional analysis, or flag abusive callers.</p> <p>Contact attributes are available in Amazon Connect for 24 months, and are then deleted.</p>', 'UpdateUserHierarchy' => '<p>Assigns the specified hierarchy group to the user.</p>', 'UpdateUserIdentityInfo' => '<p>Updates the identity information for the specified user in a <code>UserIdentityInfo</code> object, including email, first name, and last name.</p>', 'UpdateUserPhoneConfig' => '<p>Updates the phone configuration settings in the <code>UserPhoneConfig</code> object for the specified user.</p>', 'UpdateUserRoutingProfile' => '<p>Assigns the specified routing profile to a user.</p>', 'UpdateUserSecurityProfiles' => '<p>Update the security profiles assigned to the user.</p>', ], 'shapes' => [ 'ARN' => [ 'base' => NULL, 'refs' => [ 'CreateUserResponse$UserArn' => '<p>The Amazon Resource Name (ARN) of the user account created.</p>', 'HierarchyGroup$Arn' => '<p>The Amazon Resource Name (ARN) for the hierarchy group.</p>', 'HierarchyGroupSummary$Arn' => '<p>The ARN for the hierarchy group.</p>', 'HierarchyLevel$Arn' => '<p>The ARN for the hierarchy group level.</p>', 'RoutingProfileSummary$Arn' => '<p>The ARN of the routing profile.</p>', 'SecurityProfileSummary$Arn' => '<p>The ARN of the security profile.</p>', 'User$Arn' => '<p>The ARN of the user account.</p>', 'UserSummary$Arn' => '<p>The ARN for the user account.</p>', ], ], 'AfterContactWorkTimeLimit' => [ 'base' => NULL, 'refs' => [ 'UserPhoneConfig$AfterContactWorkTimeLimit' => '<p>The After Call Work (ACW) timeout setting, in seconds, for the user.</p>', ], ], 'AgentFirstName' => [ 'base' => NULL, 'refs' => [ 'UserIdentityInfo$FirstName' => '<p>The first name used in the user account. This is required if you are using Amazon Connect or SAML for identity management.</p>', ], ], 'AgentLastName' => [ 'base' => NULL, 'refs' => [ 'UserIdentityInfo$LastName' => '<p>The last name used in the user account. This is required if you are using Amazon Connect or SAML for identity management.</p>', ], ], 'AgentUsername' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$Username' => '<p>The user name in Amazon Connect for the user to create.</p>', 'User$Username' => '<p>The user name assigned to the user account.</p>', 'UserSummary$Username' => '<p>The Amazon Connect user name for the user account.</p>', ], ], 'AttributeName' => [ 'base' => 'Key for the key value pair to be used for additional attributes.', 'refs' => [ 'Attributes$key' => NULL, ], ], 'AttributeValue' => [ 'base' => 'Value for the key value pair to be used for additional attributes.', 'refs' => [ 'Attributes$value' => NULL, ], ], 'Attributes' => [ 'base' => 'Additional attributes can be provided in the request using this field. This will be passed to the contact flow execution. Client can make use of this additional info in their contact flow.', 'refs' => [ 'StartOutboundVoiceContactRequest$Attributes' => '<p>Specify a custom key-value pair using an attribute map. The attributes are standard Amazon Connect attributes, and can be accessed in contact flows just like any other contact attributes.</p> <p>There can be up to 32,768 UTF-8 bytes across all key-value pairs. Attribute keys can include only alphanumeric, dash, and underscore characters.</p> <p>For example, if you want play a greeting when the customer answers the call, you can pass the customer name in attributes similar to the following:</p>', 'UpdateContactAttributesRequest$Attributes' => '<p>The key-value pairs for the attribute to update.</p>', ], ], 'AutoAccept' => [ 'base' => NULL, 'refs' => [ 'UserPhoneConfig$AutoAccept' => '<p>The Auto accept setting for the user, Yes or No.</p>', ], ], 'ClientToken' => [ 'base' => 'Dedupe token to be provided by the client. This token is used to avoid duplicate calls to the customer.', 'refs' => [ 'StartOutboundVoiceContactRequest$ClientToken' => '<p>A unique, case-sensitive identifier that you provide to ensure the idempotency of the request. The token is valid for 7 days after creation. If a contact is already started, the contact ID is returned. If the contact is disconnected, a new contact is started.</p>', ], ], 'ContactFlowId' => [ 'base' => 'Amazon resource name for the contact flow to be executed to handle the current call.', 'refs' => [ 'StartOutboundVoiceContactRequest$ContactFlowId' => '<p>The identifier for the contact flow to connect the outbound call to.</p> <p>To find the <code>ContactFlowId</code>, open the contact flow you want to use in the Amazon Connect contact flow editor. The ID for the contact flow is displayed in the address bar as part of the URL. For example, the contact flow ID is the set of characters at the end of the URL, after \'contact-flow/\' such as <code>78ea8fd5-2659-4f2b-b528-699760ccfc1b</code>.</p>', ], ], 'ContactId' => [ 'base' => 'Amazon Connect contact identifier. An unique ContactId will be generated for each contact request.', 'refs' => [ 'StartOutboundVoiceContactResponse$ContactId' => '<p>The unique identifier of this contact within your Amazon Connect instance.</p>', 'StopContactRequest$ContactId' => '<p>The unique identifier of the contact to end.</p>', 'UpdateContactAttributesRequest$InitialContactId' => '<p>The unique identifier of the contact for which to update attributes. This is the identifier for the contact associated with the first interaction with the contact center.</p>', ], ], 'ContactNotFoundException' => [ 'base' => '<p>The contact with the specified ID is not active or does not exist.</p>', 'refs' => [], ], 'CreateUserRequest' => [ 'base' => NULL, 'refs' => [], ], 'CreateUserResponse' => [ 'base' => NULL, 'refs' => [], ], 'Credentials' => [ 'base' => '<p>The credentials to use for federation.</p>', 'refs' => [ 'GetFederationTokenResponse$Credentials' => '<p>The credentials to use for federation.</p>', ], ], 'DeleteUserRequest' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserHierarchyGroupRequest' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserHierarchyGroupResponse' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserHierarchyStructureRequest' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserHierarchyStructureResponse' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserRequest' => [ 'base' => NULL, 'refs' => [], ], 'DescribeUserResponse' => [ 'base' => NULL, 'refs' => [], ], 'DestinationNotAllowedException' => [ 'base' => '<p>Outbound calls to the destination number are not allowed.</p>', 'refs' => [], ], 'DirectoryUserId' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$DirectoryUserId' => '<p>The unique identifier for the user account in the directory service directory used for identity management. If Amazon Connect is unable to access the existing directory, you can use the <code>DirectoryUserId</code> to authenticate users. If you include the parameter, it is assumed that Amazon Connect cannot access the directory. If the parameter is not included, the UserIdentityInfo is used to authenticate users from your existing directory.</p> <p>This parameter is required if you are using an existing directory for identity management in Amazon Connect when Amazon Connect cannot access your directory to authenticate users. If you are using SAML for identity management and include this parameter, an <code>InvalidRequestException</code> is returned.</p>', 'User$DirectoryUserId' => '<p>The directory Id for the user account in the existing directory used for identity management.</p>', ], ], 'DuplicateResourceException' => [ 'base' => '<p>A resource with that name already exisits.</p>', 'refs' => [], ], 'Email' => [ 'base' => NULL, 'refs' => [ 'UserIdentityInfo$Email' => '<p>The email address added to the user account. If you are using SAML for identity management and include this parameter, an <code>InvalidRequestException</code> is returned.</p>', ], ], 'GetFederationTokenRequest' => [ 'base' => NULL, 'refs' => [], ], 'GetFederationTokenResponse' => [ 'base' => NULL, 'refs' => [], ], 'HierarchyGroup' => [ 'base' => '<p>A <code>HierarchyGroup</code> object that contains information about a hierarchy group in your Amazon Connect instance.</p>', 'refs' => [ 'DescribeUserHierarchyGroupResponse$HierarchyGroup' => '<p>Returns a <code>HierarchyGroup</code> object.</p>', ], ], 'HierarchyGroupId' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$HierarchyGroupId' => '<p>The unique identifier for the hierarchy group to assign to the user created.</p>', 'DescribeUserHierarchyGroupRequest$HierarchyGroupId' => '<p>The identifier for the hierarchy group to return.</p>', 'HierarchyGroup$Id' => '<p>The identifier for the hierarchy group.</p>', 'HierarchyGroupSummary$Id' => '<p>The identifier of the hierarchy group.</p>', 'UpdateUserHierarchyRequest$HierarchyGroupId' => '<p>The identifier for the hierarchy group to assign to the user.</p>', 'User$HierarchyGroupId' => '<p>The identifier for the hierarchy group assigned to the user.</p>', ], ], 'HierarchyGroupName' => [ 'base' => NULL, 'refs' => [ 'HierarchyGroup$Name' => '<p>The name of the hierarchy group in your instance.</p>', 'HierarchyGroupSummary$Name' => '<p>The name of the hierarchy group.</p>', ], ], 'HierarchyGroupSummary' => [ 'base' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the hierarchy group, including ARN, Id, and Name.</p>', 'refs' => [ 'HierarchyGroupSummaryList$member' => NULL, 'HierarchyPath$LevelOne' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the level of the hierarchy group, including ARN, Id, and Name.</p>', 'HierarchyPath$LevelTwo' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the level of the hierarchy group, including ARN, Id, and Name.</p>', 'HierarchyPath$LevelThree' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the level of the hierarchy group, including ARN, Id, and Name.</p>', 'HierarchyPath$LevelFour' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the level of the hierarchy group, including ARN, Id, and Name.</p>', 'HierarchyPath$LevelFive' => '<p>A <code>HierarchyGroupSummary</code> object that contains information about the level of the hierarchy group, including ARN, Id, and Name.</p>', ], ], 'HierarchyGroupSummaryList' => [ 'base' => NULL, 'refs' => [ 'ListUserHierarchyGroupsResponse$UserHierarchyGroupSummaryList' => '<p>An array of <code>HierarchyGroupSummary</code> objects.</p>', ], ], 'HierarchyLevel' => [ 'base' => '<p>A <code>HierarchyLevel</code> object that contains information about the levels in a hierarchy group, including ARN, Id, and Name.</p>', 'refs' => [ 'HierarchyStructure$LevelOne' => '<p>A <code>HierarchyLevel</code> object that contains information about the hierarchy group level.</p>', 'HierarchyStructure$LevelTwo' => '<p>A <code>HierarchyLevel</code> object that contains information about the hierarchy group level.</p>', 'HierarchyStructure$LevelThree' => '<p>A <code>HierarchyLevel</code> object that contains information about the hierarchy group level.</p>', 'HierarchyStructure$LevelFour' => '<p>A <code>HierarchyLevel</code> object that contains information about the hierarchy group level.</p>', 'HierarchyStructure$LevelFive' => '<p>A <code>HierarchyLevel</code> object that contains information about the hierarchy group level.</p>', ], ], 'HierarchyLevelId' => [ 'base' => NULL, 'refs' => [ 'HierarchyGroup$LevelId' => '<p>The identifier for the level in the hierarchy group.</p>', 'HierarchyLevel$Id' => '<p>The identifier for the hierarchy group level.</p>', ], ], 'HierarchyLevelName' => [ 'base' => NULL, 'refs' => [ 'HierarchyLevel$Name' => '<p>The name of the hierarchy group level.</p>', ], ], 'HierarchyPath' => [ 'base' => '<p>A <code>HierarchyPath</code> object that contains information about the levels of the hierarchy group.</p>', 'refs' => [ 'HierarchyGroup$HierarchyPath' => '<p>A <code>HierarchyPath</code> object that contains information about the levels in the hierarchy group.</p>', ], ], 'HierarchyStructure' => [ 'base' => '<p>A <code>HierarchyStructure</code> object that contains information about the hierarchy group structure.</p>', 'refs' => [ 'DescribeUserHierarchyStructureResponse$HierarchyStructure' => '<p>A <code>HierarchyStructure</code> object.</p>', ], ], 'InstanceId' => [ 'base' => 'Amazon Connect Organization ARN. A client must provide its organization ARN in order to place a call. This defines the call from organization.', 'refs' => [ 'CreateUserRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'DeleteUserRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'DescribeUserHierarchyGroupRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'DescribeUserHierarchyStructureRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'DescribeUserRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'GetFederationTokenRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'ListRoutingProfilesRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'ListSecurityProfilesRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'ListUserHierarchyGroupsRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'ListUsersRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'StartOutboundVoiceContactRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'StopContactRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateContactAttributesRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your Amazon Connect instance, open the AWS console and select Amazon Connect. Select the instance alias of the instance. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateUserHierarchyRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateUserIdentityInfoRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateUserPhoneConfigRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateUserRoutingProfileRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', 'UpdateUserSecurityProfilesRequest$InstanceId' => '<p>The identifier for your Amazon Connect instance. To find the ID of your instance, open the AWS console and select Amazon Connect. Select the alias of the instance in the Instance alias column. The instance ID is displayed in the Overview section of your instance settings. For example, the instance ID is the set of characters at the end of the instance ARN, after instance/, such as 10a4c4eb-f57e-4d4c-b602-bf39176ced07.</p>', ], ], 'InternalServiceException' => [ 'base' => '<p>Request processing failed due to an error or failure with the service.</p>', 'refs' => [], ], 'InvalidParameterException' => [ 'base' => '<p>One or more of the parameters provided to the operation are not valid.</p>', 'refs' => [], ], 'InvalidRequestException' => [ 'base' => '<p>The request is not valid.</p>', 'refs' => [], ], 'LimitExceededException' => [ 'base' => '<p>The allowed limit for the resource has been reached.</p>', 'refs' => [], ], 'ListRoutingProfilesRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListRoutingProfilesResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListSecurityProfilesRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListSecurityProfilesResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListUserHierarchyGroupsRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListUserHierarchyGroupsResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListUsersRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListUsersResponse' => [ 'base' => NULL, 'refs' => [], ], 'MaxResult1000' => [ 'base' => NULL, 'refs' => [ 'ListRoutingProfilesRequest$MaxResults' => '<p>The maximum number of routing profiles to return in the response.</p>', 'ListSecurityProfilesRequest$MaxResults' => '<p>The maximum number of security profiles to return.</p>', 'ListUserHierarchyGroupsRequest$MaxResults' => '<p>The maximum number of hierarchy groups to return.</p>', 'ListUsersRequest$MaxResults' => '<p>The maximum number of results to return in the response.</p>', ], ], 'Message' => [ 'base' => NULL, 'refs' => [ 'ContactNotFoundException$Message' => '<p>The message.</p>', 'DestinationNotAllowedException$Message' => '<p>The message.</p>', 'DuplicateResourceException$Message' => NULL, 'InternalServiceException$Message' => '<p>The message.</p>', 'InvalidParameterException$Message' => '<p>The message.</p>', 'InvalidRequestException$Message' => '<p>The message.</p>', 'LimitExceededException$Message' => '<p>The message.</p>', 'OutboundContactNotPermittedException$Message' => '<p>The message.</p>', 'ResourceNotFoundException$Message' => '<p>The message.</p>', 'ThrottlingException$Message' => NULL, 'UserNotFoundException$Message' => NULL, ], ], 'NextToken' => [ 'base' => NULL, 'refs' => [ 'ListRoutingProfilesRequest$NextToken' => '<p>The token for the next set of results. Use the value returned in the previous response in the next request to retrieve the next set of results.</p>', 'ListRoutingProfilesResponse$NextToken' => '<p>A string returned in the response. Use the value returned in the response as the value of the NextToken in a subsequent request to retrieve the next set of results.</p>', 'ListSecurityProfilesRequest$NextToken' => '<p>The token for the next set of results. Use the value returned in the previous response in the next request to retrieve the next set of results.</p>', 'ListSecurityProfilesResponse$NextToken' => '<p>A string returned in the response. Use the value returned in the response as the value of the NextToken in a subsequent request to retrieve the next set of results.</p>', 'ListUserHierarchyGroupsRequest$NextToken' => '<p>The token for the next set of results. Use the value returned in the previous response in the next request to retrieve the next set of results.</p>', 'ListUserHierarchyGroupsResponse$NextToken' => '<p>A string returned in the response. Use the value returned in the response as the value of the NextToken in a subsequent request to retrieve the next set of results.</p>', 'ListUsersRequest$NextToken' => '<p>The token for the next set of results. Use the value returned in the previous response in the next request to retrieve the next set of results.</p>', 'ListUsersResponse$NextToken' => '<p>A string returned in the response. Use the value returned in the response as the value of the NextToken in a subsequent request to retrieve the next set of results.</p>', ], ], 'OutboundContactNotPermittedException' => [ 'base' => '<p>The contact is not permitted.</p>', 'refs' => [], ], 'Password' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$Password' => '<p>The password for the user account to create. This is required if you are using Amazon Connect for identity management. If you are using SAML for identity management and include this parameter, an <code>InvalidRequestException</code> is returned.</p>', ], ], 'PhoneNumber' => [ 'base' => 'End customer\'s phone number to call.', 'refs' => [ 'StartOutboundVoiceContactRequest$DestinationPhoneNumber' => '<p>The phone number of the customer in E.164 format.</p>', 'StartOutboundVoiceContactRequest$SourcePhoneNumber' => '<p>The phone number, in E.164 format, associated with your Amazon Connect instance to use for the outbound call.</p>', 'UserPhoneConfig$DeskPhoneNumber' => '<p>The phone number for the user\'s desk phone.</p>', ], ], 'PhoneType' => [ 'base' => NULL, 'refs' => [ 'UserPhoneConfig$PhoneType' => '<p>The phone type selected for the user, either Soft phone or Desk phone.</p>', ], ], 'QueueId' => [ 'base' => 'Identifier of the queue to be used for the contact routing.', 'refs' => [ 'StartOutboundVoiceContactRequest$QueueId' => '<p>The queue to add the call to. If you specify a queue, the phone displayed for caller ID is the phone number specified in the queue. If you do not specify a queue, the queue used will be the queue defined in the contact flow.</p> <p>To find the <code>QueueId</code>, open the queue you want to use in the Amazon Connect Queue editor. The ID for the queue is displayed in the address bar as part of the URL. For example, the queue ID is the set of characters at the end of the URL, after \'queue/\' such as <code>queue/aeg40574-2d01-51c3-73d6-bf8624d2168c</code>.</p>', ], ], 'ResourceNotFoundException' => [ 'base' => '<p>The specified resource was not found.</p>', 'refs' => [], ], 'RoutingProfileId' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$RoutingProfileId' => '<p>The unique identifier for the routing profile to assign to the user created.</p>', 'RoutingProfileSummary$Id' => '<p>The identifier of the routing profile.</p>', 'UpdateUserRoutingProfileRequest$RoutingProfileId' => '<p>The identifier of the routing profile to assign to the user.</p>', 'User$RoutingProfileId' => '<p>The identifier of the routing profile assigned to the user.</p>', ], ], 'RoutingProfileName' => [ 'base' => NULL, 'refs' => [ 'RoutingProfileSummary$Name' => '<p>The name of the routing profile.</p>', ], ], 'RoutingProfileSummary' => [ 'base' => '<p>A <code>RoutingProfileSummary</code> object that contains information about a routing profile, including ARN, Id, and Name.</p>', 'refs' => [ 'RoutingProfileSummaryList$member' => NULL, ], ], 'RoutingProfileSummaryList' => [ 'base' => NULL, 'refs' => [ 'ListRoutingProfilesResponse$RoutingProfileSummaryList' => '<p>An array of <code>RoutingProfileSummary</code> objects that include the ARN, Id, and Name of the routing profile.</p>', ], ], 'SecurityProfileId' => [ 'base' => NULL, 'refs' => [ 'SecurityProfileIds$member' => NULL, 'SecurityProfileSummary$Id' => '<p>The identifier of the security profile.</p>', ], ], 'SecurityProfileIds' => [ 'base' => NULL, 'refs' => [ 'CreateUserRequest$SecurityProfileIds' => '<p>The unique identifier of the security profile to assign to the user created.</p>', 'UpdateUserSecurityProfilesRequest$SecurityProfileIds' => '<p>The identifiers for the security profiles to assign to the user.</p>', 'User$SecurityProfileIds' => '<p>The identifier(s) for the security profile assigned to the user.</p>', ], ], 'SecurityProfileName' => [ 'base' => NULL, 'refs' => [ 'SecurityProfileSummary$Name' => '<p>The name of the security profile.</p>', ], ], 'SecurityProfileSummary' => [ 'base' => '<p>A <code>SecurityProfileSummary</code> object that contains information about a security profile, including ARN, Id, Name.</p>', 'refs' => [ 'SecurityProfileSummaryList$member' => NULL, ], ], 'SecurityProfileSummaryList' => [ 'base' => NULL, 'refs' => [ 'ListSecurityProfilesResponse$SecurityProfileSummaryList' => '<p>An array of <code>SecurityProfileSummary</code> objects.</p>', ], ], 'SecurityToken' => [ 'base' => NULL, 'refs' => [ 'Credentials$AccessToken' => '<p>An access token generated for a federated user to access Amazon Connect</p>', 'Credentials$RefreshToken' => '<p>Renews a token generated for a user to access the Amazon Connect instance.</p>', ], ], 'StartOutboundVoiceContactRequest' => [ 'base' => NULL, 'refs' => [], ], 'StartOutboundVoiceContactResponse' => [ 'base' => NULL, 'refs' => [], ], 'StopContactRequest' => [ 'base' => NULL, 'refs' => [], ], 'StopContactResponse' => [ 'base' => NULL, 'refs' => [], ], 'ThrottlingException' => [ 'base' => '<p>The throttling limit has been exceeded.</p>', 'refs' => [], ], 'UpdateContactAttributesRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateContactAttributesResponse' => [ 'base' => NULL, 'refs' => [], ], 'UpdateUserHierarchyRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateUserIdentityInfoRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateUserPhoneConfigRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateUserRoutingProfileRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateUserSecurityProfilesRequest' => [ 'base' => NULL, 'refs' => [], ], 'User' => [ 'base' => '<p>A <code>User</code> object that contains information about a user account in your Amazon Connect instance, including configuration settings.</p>', 'refs' => [ 'DescribeUserResponse$User' => '<p>A <code>User</code> object that contains information about the user account and configuration settings.</p>', ], ], 'UserId' => [ 'base' => NULL, 'refs' => [ 'CreateUserResponse$UserId' => '<p>The unique identifier for the user account in Amazon Connect</p>', 'DeleteUserRequest$UserId' => '<p>The unique identifier of the user to delete.</p>', 'DescribeUserRequest$UserId' => '<p>Unique identifier for the user account to return.</p>', 'UpdateUserHierarchyRequest$UserId' => '<p>The identifier of the user account to assign the hierarchy group to.</p>', 'UpdateUserIdentityInfoRequest$UserId' => '<p>The identifier for the user account to update identity information for.</p>', 'UpdateUserPhoneConfigRequest$UserId' => '<p>The identifier for the user account to change phone settings for.</p>', 'UpdateUserRoutingProfileRequest$UserId' => '<p>The identifier for the user account to assign the routing profile to.</p>', 'UpdateUserSecurityProfilesRequest$UserId' => '<p>The identifier of the user account to assign the security profiles.</p>', 'User$Id' => '<p>The identifier of the user account.</p>', 'UserSummary$Id' => '<p>The identifier for the user account.</p>', ], ], 'UserIdentityInfo' => [ 'base' => '<p>A <code>UserIdentityInfo</code> object that contains information about the user\'s identity, including email address, first name, and last name.</p>', 'refs' => [ 'CreateUserRequest$IdentityInfo' => '<p>Information about the user, including email address, first name, and last name.</p>', 'UpdateUserIdentityInfoRequest$IdentityInfo' => '<p>A <code>UserIdentityInfo</code> object.</p>', 'User$IdentityInfo' => '<p>A <code>UserIdentityInfo</code> object.</p>', ], ], 'UserNotFoundException' => [ 'base' => '<p>No user with the specified credentials was found in the Amazon Connect instance.</p>', 'refs' => [], ], 'UserPhoneConfig' => [ 'base' => '<p>A <code>UserPhoneConfig</code> object that contains information about the user phone configuration settings.</p>', 'refs' => [ 'CreateUserRequest$PhoneConfig' => '<p>Specifies the phone settings for the user, including AfterContactWorkTimeLimit, AutoAccept, DeskPhoneNumber, and PhoneType.</p>', 'UpdateUserPhoneConfigRequest$PhoneConfig' => '<p>A <code>UserPhoneConfig</code> object that contains settings for <code>AfterContactWorkTimeLimit</code>, <code>AutoAccept</code>, <code>DeskPhoneNumber</code>, and <code>PhoneType</code> to assign to the user.</p>', 'User$PhoneConfig' => '<p>A <code>UserPhoneConfig</code> object.</p>', ], ], 'UserSummary' => [ 'base' => '<p>A <code>UserSummary</code> object that contains Information about a user, including ARN, Id, and user name.</p>', 'refs' => [ 'UserSummaryList$member' => NULL, ], ], 'UserSummaryList' => [ 'base' => NULL, 'refs' => [ 'ListUsersResponse$UserSummaryList' => '<p>An array of <code>UserSummary</code> objects that contain information about the users in your instance.</p>', ], ], 'timestamp' => [ 'base' => NULL, 'refs' => [ 'Credentials$AccessTokenExpiration' => '<p>A token generated with an expiration time for the session a user is logged in to Amazon Connect</p>', 'Credentials$RefreshTokenExpiration' => '<p>Renews the expiration timer for a generated token.</p>', ], ], ],];
